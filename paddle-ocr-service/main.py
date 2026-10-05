from __future__ import annotations

import io
import math
import os
import time
from typing import Any

import cv2
import numpy as np
from fastapi import FastAPI, File, Form, HTTPException, UploadFile
from fastapi.responses import Response
from PIL import Image, UnidentifiedImageError
from paddleocr import PaddleOCR

MODEL_DIR = os.environ.get("PADDLE_OCR_HOME", "")
LANG = os.environ.get("PADDLE_OCR_LANG", "en")

app = FastAPI(title="PaddleOCR Service", version="1.1.0")

ocr: PaddleOCR | None = None

# Colours used by the debug overlay returned by /ocr/visualise.
LAYOUT_COLOURS: dict[str, tuple[int, int, int]] = {
    "heading": (37, 99, 235),
    "body": (15, 140, 142),
    "list": (124, 58, 237),
    "caption": (107, 114, 128),
    "signature": (219, 39, 119),
    "table": (217, 119, 6),
    "figure": (71, 85, 105),
    "seal": (220, 38, 38),
    "qr_code": (234, 88, 12),
    "checkbox": (22, 163, 74),
}

MIN_PAGE_EDGE = 200


@app.on_event("startup")
def load_model() -> None:
    """Load PP-OCRv5 once, at boot, not per request."""
    global ocr
    started = time.time()
    ocr = PaddleOCR(
        text_detection_model_name="PP-OCRv5_server_det",
        text_recognition_model_name="PP-OCRv5_server_rec",
        use_doc_orientation_classify=False,
        use_doc_unwarping=False,
        use_textline_orientation=True,
        lang=LANG,
    )
    print(f"PaddleOCR ready in {time.time() - started:.1f}s (home={MODEL_DIR or 'default'})")


@app.get("/health")
def health() -> dict[str, Any]:
    return {"status": "ok" if ocr is not None else "loading"}


@app.post("/ocr")
async def run_ocr(
    file: UploadFile = File(...),
    extract_layout: bool = Form(True),
) -> dict[str, Any]:
    image, lines, started = await _recognise(file)

    confidences = [line["confidence"] for line in lines if line["confidence"] is not None]

    payload: dict[str, Any] = {
        "text": "\n".join(line["text"] for line in lines),
        "confidence": round(sum(confidences) / len(confidences), 4) if confidences else 0.0,
        "lines": lines,
        "line_count": len(lines),
        "engine": "PP-OCRv5_server_rec",
        "language": LANG,
        "processing_time_ms": round((time.time() - started) * 1000, 2),
        "page_size": {"width": image.width, "height": image.height},
    }

    # Layout/visual analysis is pure geometry over the boxes we already have,
    # so it costs no extra model inference and can be skipped for bulk work.
    if extract_layout:
        tables = _detect_tables(image)
        layout, _meta = _analyse_layout(lines, image, tables)
        visuals = _detect_visuals(image, lines, tables)

        payload["layout"] = layout
        payload["visuals"] = visuals

    return payload


@app.post("/ocr/visualise")
async def visualise_ocr(file: UploadFile = File(...)) -> Response:
    """Annotated PNG so the extracted layout/visuals can actually be seen."""
    image, lines, _started = await _recognise(file)

    tables = _detect_tables(image)
    layout, _meta = _analyse_layout(lines, image, tables)
    visuals = _detect_visuals(image, lines, tables)

    canvas = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)

    for block in layout:
        _draw_box(canvas, block["bbox"], LAYOUT_COLOURS.get(block["label"], (100, 100, 100)), 2)
        _draw_label(canvas, block["bbox"], f"{block['label']} {block['order']}", LAYOUT_COLOURS.get(block["label"], (100, 100, 100)))

    for visual in visuals:
        colour = LAYOUT_COLOURS.get(visual["kind"], (100, 100, 100))
        _draw_box(canvas, visual["bbox"], colour, 3)
        caption = visual["kind"]
        if visual.get("value"):
            caption = f"{caption}: {str(visual['value'])[:18]}"
        _draw_label(canvas, visual["bbox"], caption, colour)

    ok, buffer = cv2.imencode(".png", canvas)
    if not ok:
        raise HTTPException(status_code=500, detail="Could not encode the overlay image.")

    return Response(content=buffer.tobytes(), media_type="image/png")


async def _recognise(file: UploadFile) -> tuple[Image.Image, list[dict[str, Any]], float]:
    if ocr is None:
        raise HTTPException(status_code=503, detail="Model is still loading.")

    started = time.time()
    raw = await file.read()

    if not raw:
        raise HTTPException(status_code=400, detail="Empty upload.")

    try:
        image = Image.open(io.BytesIO(raw)).convert("RGB")
    except (UnidentifiedImageError, OSError) as exc:
        raise HTTPException(status_code=400, detail=f"Unreadable image: {exc}") from exc

    if image.width < MIN_PAGE_EDGE or image.height < MIN_PAGE_EDGE:
        raise HTTPException(
            status_code=400,
            detail=f"Image too small; prescriptions need at least {MIN_PAGE_EDGE}x{MIN_PAGE_EDGE} pixels.",
        )

    try:
        results = ocr.predict(np.array(image))
    except Exception as exc:  # noqa: BLE001 - surface engine failures as 500
        raise HTTPException(status_code=500, detail=f"Recognition failed: {exc}") from exc

    lines: list[dict[str, Any]] = []

    for result in results:
        payload = _result_dict(result)

        texts = payload.get("rec_texts") or []
        scores = payload.get("rec_scores") or []
        polys = payload.get("rec_polys") or payload.get("dt_polys") or []

        for index, text in enumerate(texts):
            text = (text or "").strip()

            if not text:
                continue

            score = float(scores[index]) if index < len(scores) else None
            poly = polys[index] if index < len(polys) else None

            lines.append(
                {
                    "text": text,
                    "confidence": round(score, 4) if score is not None else None,
                    "box": np.asarray(poly).tolist() if poly is not None else None,
                    "bbox": _bbox(poly),
                }
            )

    return image, lines, started


# ---------------------------------------------------------------------------
# Layout analysis
# ---------------------------------------------------------------------------


def _analyse_layout(
    lines: list[dict[str, Any]],
    image: Image.Image,
    tables: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], dict[str, Any]]:
    """Group OCR lines into ordered, typed blocks and flag the page structure."""
    usable = [line for line in lines if line.get("bbox")]

    if not usable:
        return [], {"page_width": image.width, "page_height": image.height, "column_count": 0}

    heights = sorted(b["bbox"][3] - b["bbox"][1] for b in usable)
    median_height = heights[len(heights) // 2] or 1.0

    blocks = _group_into_blocks(usable, median_height, image.width)
    columns = _detect_columns(blocks, image.width)

    regions: list[dict[str, Any]] = []

    for block in blocks:
        block["label"] = _classify_block(block, median_height)
        block["column"] = _column_of(block["bbox"], columns, image.width)

        regions.append(
            {
                "label": block["label"],
                "bbox": block["bbox"],
                "order": 0,
                "column": block["column"],
                "text": block["text"],
                "line_count": len(block["lines"]),
                "mean_height": round(block["mean_height"], 2),
            }
        )

    for table in tables:
        regions.append(
            {
                "label": "table",
                "bbox": table["bbox"],
                "order": 0,
                "column": _column_of(table["bbox"], columns, image.width),
                "text": "",
                "line_count": table.get("interior_lines", 0),
                "mean_height": 0.0,
            }
        )

    # Reading order: column by column, top to bottom inside each column.
    regions.sort(key=lambda r: (r["column"], r["bbox"][1], r["bbox"][0]))
    for index, region in enumerate(regions):
        region["order"] = index

    layout_meta = {
        "page_width": image.width,
        "page_height": image.height,
        "median_line_height": round(median_height, 2),
        "column_count": columns["count"],
        "columns": columns["bounds"],
        "block_count": len(blocks),
        "table_count": len(tables),
    }

    return regions, layout_meta


BULLETS = ("•", "·", "-", "–", "—", "*", "○", "●")


def _classify_block(block: dict[str, Any], median_height: float) -> str:
    """Headings are oversized near the top; bullets become lists; rest is body."""
    text = block["text"].lstrip()
    bbox = block["bbox"]

    if text.startswith(BULLETS) or (text[:1].isdigit() and text[1:3] in (". ", ") ")):
        return "list"

    if block["mean_height"] > median_height * 1.25:
        return "heading"

    if block["mean_height"] < median_height * 0.8 and len(block["lines"]) == 1 and bbox[1] > 0:
        return "caption"

    return "body"


def _group_into_blocks(
    lines: list[dict[str, Any]],
    median_height: float,
    page_width: int,
) -> list[dict[str, Any]]:
    """Merge lines that sit on the same visual row band into one block.

    A vertical gap, a lost horizontal overlap, or a sudden indent all end a
    block, so form fields do not collapse into one giant region.
    """
    ordered = sorted(lines, key=lambda line: (line["bbox"][1], line["bbox"][0]))

    gap_limit = median_height * 1.6
    indent_limit = page_width * 0.06

    groups: list[list[dict[str, Any]]] = [[ordered[0]]]

    for line in ordered[1:]:
        previous = groups[-1][-1]["bbox"]
        current = line["bbox"]

        vertical_gap = current[1] - previous[3]
        horizontal_overlap = min(previous[2], current[2]) - max(previous[0], current[0])
        width_ratio = min(previous[2] - previous[0], current[2] - current[0])

        group_left = min(item["bbox"][0] for item in groups[-1])
        indent_jump = current[0] - group_left

        starts_new_group = (
            vertical_gap > gap_limit
            or (width_ratio > 0 and horizontal_overlap < 0)
            or indent_jump > indent_limit
        )

        if starts_new_group:
            groups.append([line])
        else:
            groups[-1].append(line)

    blocks: list[dict[str, Any]] = []

    for group in groups:
        x1 = min(line["bbox"][0] for line in group)
        y1 = min(line["bbox"][1] for line in group)
        x2 = max(line["bbox"][2] for line in group)
        y2 = max(line["bbox"][3] for line in group)
        heights = [line["bbox"][3] - line["bbox"][1] for line in group]
        text = " ".join(line["text"] for line in group)

        blocks.append(
            {
                "bbox": [int(x1), int(y1), int(x2), int(y2)],
                "text": text,
                "lines": group,
                "mean_height": sum(heights) / len(heights),
                "label": "body",
            }
        )

    return blocks


def _detect_columns(blocks: list[dict[str, Any]], page_width: int) -> dict[str, Any]:
    """A gutter is only real when every block sits clear of it.

    ``left``/``right`` bound the widest gap that all blocks avoid. If any block
    crosses that span there is no column structure, and pretending otherwise
    produces overlapping, meaningless column bounds.
    """
    if len(blocks) < 4:
        return {"count": 1, "bounds": [[0, page_width]]}

    left = max(block["bbox"][0] for block in blocks)
    right = min(block["bbox"][2] for block in blocks)

    if left - right <= page_width * 0.06:
        return {"count": 1, "bounds": [[0, page_width]]}

    for block in blocks:
        if block["bbox"][0] < left and block["bbox"][2] > right:
            return {"count": 1, "bounds": [[0, page_width]]}

    return {"count": 2, "bounds": [[0, right], [left, page_width]]}


def _column_of(bbox: list[int], columns: dict[str, Any], page_width: int) -> int:
    """Assign a region to a column, clamping to the nearest one when the centre
    lands inside the gutter between columns."""
    centre = (bbox[0] + bbox[2]) / 2

    for index, (start, end) in enumerate(columns["bounds"]):
        if start <= centre <= end:
            return index

    distances = [abs(centre - start) for start, _ in columns["bounds"]]

    return distances.index(min(distances)) if distances else 0


# ---------------------------------------------------------------------------
# Visual / structural detection
# ---------------------------------------------------------------------------


def _detect_tables(image: Image.Image) -> list[dict[str, Any]]:
    """Ruling lines that enclose text look like a table; a page border does not."""
    page = np.array(image)
    height, width = page.shape[:2]
    gray = cv2.cvtColor(page, cv2.COLOR_RGB2GRAY)

    binary = cv2.adaptiveThreshold(
        gray, 255, cv2.ADAPTIVE_THRESH_MEAN_C, cv2.THRESH_BINARY_INV, 15, -2
    )

    kernel_width = max(20, width // 25)
    kernel_height = max(20, height // 25)

    horizontal = cv2.morphologyEx(
        binary,
        cv2.MORPH_OPEN,
        cv2.getStructuringElement(cv2.MORPH_RECT, (kernel_width, 1)),
    )
    vertical = cv2.morphologyEx(
        binary,
        cv2.MORPH_OPEN,
        cv2.getStructuringElement(cv2.MORPH_RECT, (1, kernel_height)),
    )

    grid = cv2.dilate(
        cv2.add(horizontal, vertical),
        np.ones((5, 5), np.uint8),
        iterations=2,
    )

    contours, _ = cv2.findContours(grid, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    tables: list[dict[str, Any]] = []

    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)

        if w < width * 0.15 or h < height * 0.05:
            continue

        # Reject a plain page frame: it hugs the edges and spans the page.
        if w >= width * 0.97 and h >= height * 0.97:
            continue
        if x <= width * 0.02 and y <= height * 0.02 and w >= width * 0.9 and h >= height * 0.9:
            continue

        tables.append(
            {
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "row_lines": _count_ruling_lines(horizontal[y : y + h, x : x + w], axis="rows"),
                "column_lines": _count_ruling_lines(vertical[y : y + h, x : x + w], axis="columns"),
            }
        )

    return tables


def _count_ruling_lines(mask: np.ndarray, axis: str) -> int:
    if mask.size == 0:
        return 0

    projection = (mask > 0).sum(axis=1 if axis == "rows" else 0)
    threshold = projection.max() * 0.5 if projection.size else 0

    return int((projection >= max(threshold, 1)).sum())


def _detect_visuals(
    image: Image.Image,
    lines: list[dict[str, Any]],
    tables: list[dict[str, Any]],
) -> list[dict[str, Any]]:
    page = np.array(image)
    height, width = page.shape[:2]
    page_area = float(width * height)

    text_boxes = [line["bbox"] for line in lines if line.get("bbox")]
    visuals: list[dict[str, Any]] = []

    for quad in _find_qr_codes(page):
        x, y, w, h = cv2.boundingRect(quad.astype(np.int32))
        visuals.append(
            {
                "kind": "qr_code",
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "value": _decode_qr(page, quad),
                "confidence": 0.95,
            }
        )

    visuals.extend(_find_seals(page, page_area, text_boxes))
    visuals.extend(_find_signatures(page, page_area, text_boxes, height))
    visuals.extend(_find_checkboxes(page, text_boxes))

    for table in tables:
        interior = sum(
            1
            for box in text_boxes
            if _inside(table["bbox"], box)
        )
        table["interior_lines"] = interior
        visuals.append(
            {
                "kind": "table",
                "bbox": table["bbox"],
                "row_lines": table["row_lines"],
                "column_lines": table["column_lines"],
                "interior_lines": interior,
                "confidence": round(min(0.95, 0.4 + interior * 0.08), 2),
            }
        )

    visuals.extend(_find_figures(page, page_area, text_boxes, tables))

    visuals = _dedupe_visuals(visuals)

    visuals.sort(key=lambda visual: (visual["bbox"][1], visual["bbox"][0]))

    return visuals


def _dedupe_visuals(visuals: list[dict[str, Any]]) -> list[dict[str, Any]]:
    """Contour passes report the same square twice; keep the best of each pair."""
    kept: list[dict[str, Any]] = []

    for visual in visuals:
        duplicate = False

        for existing in kept:
            if existing["kind"] != visual["kind"]:
                continue

            if _iou(existing["bbox"], visual["bbox"]) > 0.4:
                duplicate = True

                if visual.get("confidence", 0) > existing.get("confidence", 0):
                    existing.update(visual)

                break

        if not duplicate:
            kept.append(visual)

    return kept


def _iou(first: list[int], second: list[int]) -> float:
    overlap_w = min(first[2], second[2]) - max(first[0], second[0])
    overlap_h = min(first[3], second[3]) - max(first[1], second[1])

    if overlap_w <= 0 or overlap_h <= 0:
        return 0.0

    intersection = overlap_w * overlap_h
    area_first = (first[2] - first[0]) * (first[3] - first[1])
    area_second = (second[2] - second[0]) * (second[3] - second[1])

    union = area_first + area_second - intersection

    return intersection / union if union > 0 else 0.0


def _find_qr_codes(page: np.ndarray) -> list[np.ndarray]:
    """detect() returns (found, points) where points has shape (n, 4, 2)."""
    detector = cv2.QRCodeDetector()

    try:
        found, points = detector.detect(page)
    except cv2.error:
        return []

    if not found or points is None:
        return []

    points = np.asarray(points, dtype=np.float32)

    if points.ndim == 2:
        points = points.reshape(1, -1, 2)

    quads: list[np.ndarray] = []

    for quad in points:
        if quad.shape == (4, 2):
            quads.append(quad)

    return quads


def _decode_qr(page: np.ndarray, points: Any) -> str | None:
    detector = cv2.QRCodeDetector()

    try:
        value, _points, _straight = detector.detectAndDecode(page)
    except cv2.error:
        return None

    if value:
        return str(value)

    warped = _warp_quad(page, points)

    if warped is None:
        return None

    try:
        value, _points, _straight = detector.detectAndDecode(warped)
    except cv2.error:
        return None

    return str(value) if value else None


def _warp_quad(page: np.ndarray, points: Any) -> np.ndarray | None:
    try:
        ordered = _order_quad(np.asarray(points, dtype=np.float32))
    except (ValueError, np.linalg.LinAlgError):
        return None

    if ordered is None:
        return None

    width = int(max(np.linalg.norm(ordered[0] - ordered[1]), np.linalg.norm(ordered[2] - ordered[3])))
    height = int(max(np.linalg.norm(ordered[0] - ordered[3]), np.linalg.norm(ordered[1] - ordered[2])))

    if width <= 0 or height <= 0:
        return None

    destination = np.array(
        [[0, 0], [width - 1, 0], [width - 1, height - 1], [0, height - 1]],
        dtype=np.float32,
    )

    try:
        matrix = cv2.getPerspectiveTransform(ordered, destination)
        return cv2.warpPerspective(page, matrix, (width, height))
    except cv2.error:
        return None


def _order_quad(points: np.ndarray) -> np.ndarray | None:
    if points.shape != (4, 2):
        return None

    ordered = sorted(points, key=lambda point: point[0] + point[1])

    if ordered[0][0] > ordered[3][0]:
        ordered[0], ordered[3] = ordered[3], ordered[0]

    if ordered[1][0] > ordered[2][0]:
        ordered[1], ordered[2] = ordered[2], ordered[1]

    return np.array(ordered, dtype=np.float32)


def _find_seals(page: np.ndarray, page_area: float, text_boxes: list[list[int]]) -> list[dict[str, Any]]:
    """Coloured ring/blob contours: the shape stamps and logos actually make.

    A seal is compact and roughly square as well as round, which is what keeps
    saturated headings and coloured table text out of the results.
    """
    hsv = cv2.cvtColor(page, cv2.COLOR_RGB2HSV)
    saturation, value = hsv[:, :, 1], hsv[:, :, 2]

    mask = cv2.inRange(hsv, np.array([0, 60, 40]), np.array([180, 255, 255]))
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, np.ones((5, 5), np.uint8))

    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    seals: list[dict[str, Any]] = []

    for contour in contours:
        area = cv2.contourArea(contour)

        if area < page_area * 0.003 or area > page_area * 0.20:
            continue

        x, y, w, h = cv2.boundingRect(contour)

        aspect = w / float(h) if h else 0.0

        if not 0.7 <= aspect <= 1.45:
            continue

        circularity = _circularity(contour)

        if circularity < 0.6:
            continue

        if _overlaps_text([x, y, x + w, y + h], text_boxes, threshold=0.7):
            continue

        filled = float((mask[y : y + h, x : x + w] > 0).mean())
        seals.append(
            {
                "kind": "seal",
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "circularity": round(circularity, 3),
                "aspect_ratio": round(aspect, 2),
                "fill_ratio": round(filled, 3),
                "mean_saturation": round(float(saturation[y : y + h, x : x + w].mean()), 1),
                "mean_value": round(float(value[y : y + h, x : x + w].mean()), 1),
                "confidence": round(min(0.95, circularity), 3),
            }
        )

    return seals


def _circularity(contour: np.ndarray) -> float:
    perimeter = cv2.arcLength(contour, True)

    if perimeter <= 0:
        return 0.0

    return float(cv2.contourArea(contour) / (perimeter**2 / (4 * math.pi)))


def _find_signatures(
    page: np.ndarray,
    page_area: float,
    text_boxes: list[list[int]],
    page_height: int,
) -> list[dict[str, Any]]:
    """Sparse irregular ink in the lower third, too messy to be typed text.

    Solid bars and underlines are rejected: a real signature has a low fill
    ratio and a ragged outline rather than four straight edges.
    """
    gray = cv2.cvtColor(page, cv2.COLOR_RGB2GRAY)
    ink = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_MEAN_C, cv2.THRESH_BINARY_INV, 21, -8)

    lower_top = int(page_height * 0.62)
    region = np.zeros_like(ink)
    region[lower_top:, :] = ink[lower_top:, :]

    contours, _ = cv2.findContours(region, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    signatures: list[dict[str, Any]] = []

    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)
        area = w * h

        if area < page_area * 0.004 or area > page_area * 0.30:
            continue
        if w < h * 1.3:
            continue
        if h > page_height * 0.12:
            continue

        if _overlaps_text([x, y, x + w, y + h], text_boxes, threshold=0.5):
            continue

        fill = cv2.contourArea(contour) / float(area)

        if fill > 0.55:
            continue

        vertices = len(cv2.approxPolyDP(contour, 0.02 * cv2.arcLength(contour, True), True))

        if vertices < 5:
            continue

        signatures.append(
            {
                "kind": "signature",
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "fill_ratio": round(fill, 3),
                "aspect_ratio": round(w / float(h), 2),
                "vertices": vertices,
                "confidence": round(min(0.9, 0.4 + (0.55 - fill)), 3),
            }
        )

    return signatures


def _find_checkboxes(page: np.ndarray, text_boxes: list[list[int]]) -> list[dict[str, Any]]:
    """Small hollow squares, usually on lab-result and questionnaire forms."""
    gray = cv2.cvtColor(page, cv2.COLOR_RGB2GRAY)
    edges = cv2.Canny(gray, 60, 180)

    contours, _ = cv2.findContours(edges, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)

    checkboxes: list[dict[str, Any]] = []

    for contour in contours:
        x, y, w, h = cv2.boundingRect(contour)

        if not (8 <= w <= 60 and 8 <= h <= 60):
            continue
        if abs(w - h) > max(4, 0.25 * max(w, h)):
            continue

        approximation = cv2.approxPolyDP(contour, 0.03 * cv2.arcLength(contour, True), True)

        if len(approximation) != 4:
            continue

        aspect = w / float(h)
        if not 0.75 <= aspect <= 1.35:
            continue

        if _overlaps_text([x, y, x + w, y + h], text_boxes, threshold=0.35):
            continue

        checkboxes.append(
            {
                "kind": "checkbox",
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "confidence": 0.7,
            }
        )

    return checkboxes[:60]


def _find_figures(
    page: np.ndarray,
    page_area: float,
    text_boxes: list[list[int]],
    tables: list[dict[str, Any]],
) -> list[dict[str, Any]]:
    """Ink that is neither a recognised text box nor a ruling line."""
    gray = cv2.cvtColor(page, cv2.COLOR_RGB2GRAY)
    mask = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_MEAN_C, cv2.THRESH_BINARY_INV, 25, -10)

    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, np.ones((7, 7), np.uint8))

    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    figures: list[dict[str, Any]] = []

    for contour in contours:
        area = cv2.contourArea(contour)

        if area < page_area * 0.02 or area > page_area * 0.6:
            continue

        x, y, w, h = cv2.boundingRect(contour)

        if _overlaps_text([x, y, x + w, y + h], text_boxes, threshold=0.6):
            continue

        if any(_inside(table["bbox"], [x, y, x + w, y + h]) for table in tables):
            continue

        figures.append(
            {
                "kind": "figure",
                "bbox": [int(x), int(y), int(x + w), int(y + h)],
                "area_ratio": round(area / page_area, 4),
                "confidence": 0.6,
            }
        )

    return figures[:12]


# ---------------------------------------------------------------------------
# Geometry helpers
# ---------------------------------------------------------------------------


def _bbox(poly: Any) -> list[int] | None:
    if poly is None:
        return None

    points = np.asarray(poly, dtype=np.float32)

    if points.size < 4:
        return None

    points = points.reshape(-1, 2)

    return [
        int(points[:, 0].min()),
        int(points[:, 1].min()),
        int(points[:, 0].max()),
        int(points[:, 1].max()),
    ]


def _inside(outer: list[int], inner: list[int]) -> bool:
    centre_x = (inner[0] + inner[2]) / 2
    centre_y = (inner[1] + inner[3]) / 2

    return outer[0] <= centre_x <= outer[2] and outer[1] <= centre_y <= outer[3]


def _overlaps_text(box: list[int], text_boxes: list[list[int]], threshold: float) -> bool:
    """True when the box sits mostly on top of recognised text."""
    area = max(1, (box[2] - box[0]) * (box[3] - box[1]))
    covered = 0

    for text_box in text_boxes:
        overlap_w = min(box[2], text_box[2]) - max(box[0], text_box[0])
        overlap_h = min(box[3], text_box[3]) - max(box[1], text_box[1])

        if overlap_w > 0 and overlap_h > 0:
            covered += overlap_w * overlap_h

    return covered / area > threshold


def _draw_box(canvas: np.ndarray, bbox: list[int], colour: tuple[int, int, int], thickness: int) -> None:
    cv2.rectangle(canvas, (bbox[0], bbox[1]), (bbox[2], bbox[3]), colour, thickness)


def _draw_label(canvas: np.ndarray, bbox: list[int], text: str, colour: tuple[int, int, int]) -> None:
    (width, height), baseline = cv2.getTextSize(text, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
    top = max(0, bbox[1] - height - baseline - 2)

    cv2.rectangle(canvas, (bbox[0], top), (bbox[0] + width + 4, top + height + baseline + 2), colour, -1)
    cv2.putText(
        canvas,
        text,
        (bbox[0] + 2, top + height),
        cv2.FONT_HERSHEY_SIMPLEX,
        0.5,
        (255, 255, 255),
        1,
        cv2.LINE_AA,
    )


def _result_dict(result: Any) -> dict[str, Any]:
    """PaddleX result objects differ slightly across releases; normalise them."""
    if isinstance(result, dict):
        return result.get("res", result)

    payload = getattr(result, "json", None)

    if callable(payload):
        try:
            payload = payload()
        except Exception:  # noqa: BLE001
            payload = None

    if isinstance(payload, dict):
        return payload.get("res", payload)

    return {}