import type {
    EchoData,
    DopplerData,
    TMTData,
    ManualDraftData,
} from '@/types/appointment';

// ─────────────────────────────────────────────────────────────────────────────
// Internal helper
// ─────────────────────────────────────────────────────────────────────────────

/** Returns the value as a string, or '' if null / undefined. */
const v = (x: string | undefined | null): string => x ?? '';

// ─────────────────────────────────────────────────────────────────────────────
// Shared document shell
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Wraps an HTML body fragment in a Word-compatible full HTML document shell.
 *
 * - Legal (8.5 × 14 in) paper via `@page`
 * - Arial font, 8 – 11 pt
 * - 1 px solid black borders with 5 px cell padding on all tables
 * - `.no-border` table variant, `.page-break`, and `.signature-block` helpers
 * - `@media print` colour-adjust rule for accurate PDF output
 */
export const buildDocument = (
    title: string,
    bodyHtml: string,
): string => `<!DOCTYPE html>
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
  <meta charset='utf-8'>
  <title>${title}</title>
  <style>
    @page {
      size: 8.5in 11in;
      margin: 0.5in;
    }
    body {
      font-family: Arial, sans-serif;
      font-size: 8pt;
      line-height: 1.25;
      color: #000;
      margin: 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 3px;
    }
    table.no-border td,
    table.no-border th {
      border: none !important;
    }
    td, th {
      padding: 2px 3px;
      border: 1px solid #000;
      vertical-align: middle;
      font-size: 7pt;
    }
    b, strong { font-weight: bold; }
    h2 {
      text-align: center;
      font-size: 10pt;
      margin: 3px 0 2px;
      text-transform: uppercase;
    }
    h3 {
      font-size: 8pt;
      margin: 4px 0 2px;
      text-transform: uppercase;
    }
    h4 {
      font-size: 8pt;
      margin: 4px 0 2px;
    }
    p { margin: 1px 0; }
    .page-break {
      page-break-before: always;
      break-before: page;
    }
    .signature-block {
      width: 100%;
      margin-top: 35px;
      border-collapse: collapse;
    }
    .signature-block td { border: none; }
    .signature-line {
      border-top: 1px solid #000 !important;
      text-align: center;
      padding-top: 5px;
      width: 42%;
    }
    .bg-gray { background-color: #e5e5e5; }
    @media print {
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      @page { size: 8.5in 11in; margin: 0.5in; }
    }
  </style>
</head>
<body>
${bodyHtml}
</body>
</html>`;

// ─────────────────────────────────────────────────────────────────────────────
// Export functions
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Downloads the HTML body as a `.docx` file using a Word-compatible HTML blob
 * (`application/msword`).  Works in all modern browsers without extra libraries.
 */
export const exportAsDocx = (bodyHtml: string, filename: string): void => {
    const fullHtml = buildDocument(filename, bodyHtml);
    // BOM (\uFEFF) ensures Word recognises the UTF-8 encoding.
    const blob = new Blob(['\uFEFF', fullHtml], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.docx') ? filename : `${filename}.docx`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1500);
};

/**
 * Opens the browser's native print dialog via a hidden `<iframe>`.
 * The user selects "Save as PDF" (or a physical printer) from the dialog.
 * No external libraries required.
 */
export const exportAsPdf = (bodyHtml: string, title: string): void => {
    const fullHtml = buildDocument(title, bodyHtml);

    const iframe = document.createElement('iframe');
    iframe.setAttribute(
        'style',
        'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;',
    );
    document.body.appendChild(iframe);

    const iframeDoc = iframe.contentWindow?.document;

    if (!iframeDoc) {
        document.body.removeChild(iframe);

        return;
    }

    iframeDoc.open();
    iframeDoc.write(fullHtml);
    iframeDoc.close();

    // Give the browser time to render before triggering the print dialog.
    setTimeout(() => {
        try {
            iframe.contentWindow?.focus();
            iframe.contentWindow?.print();
        } finally {
            // Remove the iframe only after the dialog has had time to open.
            setTimeout(() => {
                if (iframe.parentNode) {
                    document.body.removeChild(iframe);
                }
            }, 2000);
        }
    }, 600);
};

// ─────────────────────────────────────────────────────────────────────────────
// Private shared fragments
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Renders the 5-column patient information header used by Echo reports.
 * Pass empty strings for any field not available for a particular report type.
 */
const fullPatientHeader = (
    patientName: string,
    date: string,
    contactNo: string,
    requestingPhysician: string,
    fileNo: string,
    bsa: string,
    age: string,
    sex: string,
    height: string,
    weight: string,
): string => `
<table>
  <tr>
    <td colspan="3"><b>Patient Name:</b> ${patientName}</td>
    <td colspan="2"><b>Contact No:</b> ${contactNo}</td>
  </tr>
  <tr>
    <td colspan="5"><b>Requesting Physician:</b> ${requestingPhysician}</td>
  </tr>
  <tr>
    <td><b>File #:</b> ${fileNo}</td>
    <td><b>BSA:</b> ${bsa}</td>
    <td colspan="3"><b>Date:</b> ${date}</td>
  </tr>
  <tr>
    <td><b>Age:</b> ${age}</td>
    <td><b>Sex:</b> ${sex}</td>
    <td><b>Height:</b> ${height} cm</td>
    <td colspan="2"><b>Weight:</b> ${weight} kgs.</td>
  </tr>
</table>`;

/**
 * Renders the doctor signature block aligned to the right of the page.
 */
const signatureBlock = (doctorName?: string): string => `
<table style="width:100%;border-collapse:collapse;margin-top:20px;">
  <tr>
    <td style="width:58%;border:none;"></td>
    <td style="width:42%;border:none;border-top:1px solid #000;text-align:center;padding-top:3px;">
      ${doctorName ? `<b>${doctorName}</b><br>` : ''}<b>Cardiologist</b>
    </td>
  </tr>
</table>`;

// ─────────────────────────────────────────────────────────────────────────────
// Echo report builder
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Builds the **Echocardiographic Report** HTML body.
 *
 * Layout: A 6-column two-panel table (label | value | unit on each side)
 * with bold section headers that span their three-column panel.
 * No shaded header rows — every cell uses the same plain 1 px border style.
 */
export const buildEchoHTML = (
    ec: EchoData,
    patientName: string,
    date: string,
    doctorName?: string,
): string => {
    const header = fullPatientHeader(
        patientName,
        date,
        v(ec.contact_no),
        v(ec.requesting_physician),
        v(ec.file_no),
        v(ec.bsa),
        v(ec.age),
        v(ec.sex),
        v(ec.height),
        v(ec.weight),
    );

    // ── 6-column data table ────────────────────────────────────────────────
    // Columns 1-3 → Left panel  : label | value | unit
    // Columns 4-6 → Right panel : label | value | unit
    // Section headers use colspan="3" to span their panel.
    // "Systolic Notching" value has no unit so its value cell spans 2 cols.
    const dataTable = `
<table>
  <colgroup>
    <col style="width:24%"><col style="width:9%"><col style="width:7%">
    <col style="width:24%"><col style="width:9%"><col style="width:7%">
  </colgroup>

  <tr>
    <td colspan="3"><b>Mitral Valve</b></td>
    <td colspan="3"><b>Interventricular Septum</b></td>
  </tr>
  <tr>
    <td>ED Velocity</td><td>${v(ec.mitral_ef)}</td><td>mm&sup2;</td>
    <td>Diastolic diameter</td><td>${v(ec.ivs_dia)}</td><td>mm</td>
  </tr>
  <tr>
    <td>DE Excursion</td><td>${v(ec.mitral_de)}</td><td>mm</td>
    <td>Systolic diameter</td><td>${v(ec.ivs_sys)}</td><td>mm</td>
  </tr>
  <tr>
    <td>EPSS</td><td>${v(ec.mitral_epss)}</td><td>mm</td>
    <td>Excursion</td><td>${v(ec.ivs_exc)}</td><td>mm</td>
  </tr>
  <tr>
    <td>Area by planimetry</td><td>${v(ec.mitral_area)}</td><td>cm&sup2;</td>
    <td colspan="3"></td>
  </tr>

  <tr>
    <td colspan="3"><b>Tricuspid Valve:</b>&nbsp;${v(ec.tricuspid_valve)}</td>
    <td colspan="3"><b>LV Posterior Wall</b></td>
  </tr>
  <tr>
    <td colspan="3"></td>
    <td>Diastolic diameter</td><td>${v(ec.lvpw_dia)}</td><td>mm</td>
  </tr>

  <tr>
    <td colspan="3"><b>Aortic Valve</b></td>
    <td>Systolic diameter</td><td>${v(ec.lvpw_sys)}</td><td>mm</td>
  </tr>
  <tr>
    <td>Root Diameter</td><td>${v(ec.aortic_root)}</td><td>mm</td>
    <td>Excursion</td><td>${v(ec.lvpw_exc)}</td><td>mm</td>
  </tr>
  <tr>
    <td>Valve Opening</td><td>${v(ec.aortic_valve)}</td><td>mm</td>
    <td colspan="3"><b>Left Ventricle</b></td>
  </tr>
  <tr>
    <td>Excursion</td><td>${v(ec.aortic_excursion)}</td><td>mm</td>
    <td>Diastolic diameter</td><td>${v(ec.lv_dia)}</td><td>mm</td>
  </tr>

  <tr>
    <td colspan="3"><b>Right Atrium</b></td>
    <td>Systolic diameter</td><td>${v(ec.lv_sys)}</td><td>mm</td>
  </tr>
  <tr>
    <td>RL Diameter</td><td>${v(ec.ra_rl)}</td><td>mm</td>
    <td>LVEDV</td><td>${v(ec.lv_lvedv)}</td><td>mL</td>
  </tr>

  <tr>
    <td colspan="3"><b>Pulmonic Valve</b></td>
    <td>LVESV</td><td>${v(ec.lv_lvesv)}</td><td>mL</td>
  </tr>
  <tr>
    <td>Root Diameter</td><td>${v(ec.pulmonic_root)}</td><td>mm</td>
    <td>LV mass index</td><td>${v(ec.lv_mass)}</td><td>gm/m&sup2;</td>
  </tr>
  <tr>
    <td>Systolic Notching</td><td colspan="2">${v(ec.pulmonic_notching)}</td>
    <td>Relative wall thickness</td><td>${v(ec.lv_rwt)}</td><td>mm</td>
  </tr>

  <tr>
    <td colspan="3"><b>Left Atrium</b></td>
    <td>Ejection Fraction</td><td>${v(ec.lv_ef)}</td><td>%</td>
  </tr>
  <tr>
    <td>AP</td><td>${v(ec.la_ap)}</td><td>mm</td>
    <td>Fractional Shortening</td><td>${v(ec.lv_fs)}</td><td>%</td>
  </tr>
  <tr>
    <td>RL Diameter</td><td>${v(ec.la_rl)}</td><td>mm</td>
    <td>Heart Rate</td><td>${v(ec.hr)}</td><td>bpm</td>
  </tr>
  <tr>
    <td>LAVI</td><td>${v(ec.la_lavi)}</td><td>/mL&nbsp;m&sup2;</td>
    <td>Simpson's</td><td>${v(ec.simpsons)}</td><td>%</td>
  </tr>

  <tr>
    <td colspan="3"><b>Right Ventricle</b></td>
    <td>Tapse</td><td>${v(ec.tapse)}</td><td>cm</td>
  </tr>
  <tr>
    <td>AP Diameter</td><td>${v(ec.rv_ap)}</td><td>mm</td>
    <td colspan="3"><b>Pericardial Space</b></td>
  </tr>
  <tr>
    <td>RL Diameter</td><td>${v(ec.rv_rl)}</td><td>mm</td>
    <td>Anterior Effusion</td><td>${v(ec.peri_ant)}</td><td>mm</td>
  </tr>
  <tr>
    <td colspan="3"></td>
    <td>Posterior Effusion</td><td>${v(ec.peri_post)}</td><td>mm</td>
  </tr>

  <tr>
    <td><b>RVOT</b></td><td>${v(ec.rvot)}</td><td>mm</td>
    <td colspan="3"></td>
  </tr>
  <tr>
    <td><b>LVOT</b></td><td>${v(ec.lvot)}</td><td>mm</td>
    <td colspan="3"></td>
  </tr>
</table>`;

    return `
<h2>Echocardiographic Report</h2>
${header}
${dataTable}
<h4>ECHOCARDIOGRAPHIC FINDINGS:</h4>
<p style="white-space:pre-wrap;min-height:35px;">${v(ec.findings)}</p>
${signatureBlock(doctorName)}`;
};

// ─────────────────────────────────────────────────────────────────────────────
// TMT & Doppler canonical document builders
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Builds the full 2-page **Treadmill Exercise Test** Word export body HTML.
 * Page 1: Informed Consent form (Cardiovascular Care Center clinical format)
 * Page 2: Exercise Testing Report with interpretation and cardiologist signature
 *
 * This is the canonical implementation — TmtForm.vue delegates here.
 */
export const buildTmtDocument = (
    tmt: TMTData,
    patientName: string,
    echoSex: string,
    date: string,
): string => {
    const stagesHtml = (tmt.stages ?? [])
        .map((s) => {
            const isRec = s.name.includes('Recovery') || s.name.includes('min');

            return `<tr>
            <td ${!isRec ? 'class="bg-gray"' : ''}>${v(s.name)}</td>
            <td>${v(s.speed)}</td><td>${v(s.grade)}</td><td>${v(s.mets)}</td>
            <td>${v(s.bp)}</td><td>${v(s.hr)}</td><td>${v(s.remarks)}</td>
        </tr>`;
        })
        .join('');

    const page1 = `
    <div style="text-align:center;font-family:Arial;">
      <h1 style="margin:0;font-size:24px;color:#333;">&#9825; CARDIOVASCULAR CARE CENTER</h1>
      <p style="margin:0;font-size:10px;">Rm. 303 / 305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila<br>Tel. No: 8-353-7182</p>
    </div>
    <div style="border-bottom:2px solid #ccc;margin:10px 0;"></div>
    <h3 style="text-align:center;text-decoration:underline;font-size:14px;">INFORMED CONSENT FOR TREADMILL EXERCISE TEST</h3>
    <p style="text-align:center;text-decoration:underline;font-weight:bold;font-size:12px;">{ Please Fill Out the blanks for Information with RED ARROW CHECK }</p>
    <table style="width:100%;font-size:11px;margin-top:15px;border:none;" cellpadding="2">
        <tr><td style="width:15%;border:none;">Date</td><td style="width:35%;border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${date}</td>
            <td style="width:50%;border:none;" rowspan="5">
                <table style="width:100%;border:none;">
                    <tr><td style="border:none;">PMHR :</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">${v(tmt.pmhr)}</td></tr>
                    <tr><td colspan="2" style="border:none;height:10px;"></td></tr>
                    <tr><td style="border:none;">BP :</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">${v(tmt.bp)}</td></tr>
                </table>
            </td>
        </tr>
        <tr><td style="border:none;">Age</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.age)}</td></tr>
        <tr><td style="border:none;">Height</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.height)}</td></tr>
        <tr><td style="border:none;">Weight</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.weight)}</td></tr>
        <tr><td style="border:none;">Cell No</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.cell_no)}</td></tr>
        <tr><td style="border:none;">Birthday</td><td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.birthday)}</td><td style="border:none;"></td></tr>
        <tr><td style="border:none;">Medicines</td><td colspan="2" style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">&#10140; ${v(tmt.medicines)}</td></tr>
    </table>
    <p style="font-size:11px;">(Beta Blockers medicines such as: Carvedilol, Cardipress, Clonipress, Bisoprolol, Metoprolol, Atenolol, Calcibloc, Neobloc, etc.)</p>
    <table style="width:100%;font-size:11px;margin-top:15px;border:none;" cellpadding="2">
        <tr>
            <td style="width:25%;border:none;">Requesting Physician</td><td style="width:5%;border:none;">:</td>
            <td style="width:30%;border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">${v(tmt.requesting_physician)}</td>
            <td style="width:15%;border:none;text-align:right;">Procedure</td><td style="width:5%;border:none;text-align:center;">:</td>
            <td style="width:20%;border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">${v(tmt.procedure)}</td>
        </tr>
        <tr>
            <td style="border:none;">Attending Physician</td><td style="border:none;">:</td>
            <td style="border-bottom:1px solid black;border-top:none;border-left:none;border-right:none;">${v(tmt.attending_physician)}</td>
            <td colspan="3" style="border:none;"></td>
        </tr>
    </table>
    <p style="font-size:11px;margin-top:20px;"><b>TO WHOM IT MAY CONCERN:</b></p>
    <p style="font-size:11px;line-height:1.8;text-indent:30px;">
        I, &#10140; <u style="font-weight:bold;">${patientName}</u> of legal age and with residence and postal address at <u style="font-weight:bold;">${v(tmt.residence)}</u> hereby consent to the performance of Treadmill Exercise Test on me by <u style="font-weight:bold;">${v(tmt.consent_to) || 'the attending physician'}</u>.
    </p>
    <p style="font-size:11px;line-height:1.8;text-indent:30px;">This consent was given after the above have been fully explained in simple, adequate, and understandable language to me by the doctors concerned including the risks involved or potential consequence.</p>
    <p style="font-size:11px;line-height:1.8;text-indent:30px;">IN WITNESS WHEREOF, I hereunto affix my signature / right "thumb mark" this <u style="font-weight:bold;">${v(tmt.consent_day) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u> day of <u style="font-weight:bold;">${v(tmt.consent_month) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u> 20 at <u style="font-weight:bold;">${v(tmt.consent_place) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u>.</p>
    <table style="width:100%;font-size:11px;margin-top:40px;border:none;">
        <tr><td style="width:40%;border:none;"></td><td style="width:60%;border-top:1px solid black;text-align:center;border-left:none;border-right:none;border-bottom:none;"><span style="float:left;">&#10140;</span>(Signature over printed name of patient or person giving free consent or his right "thumb mark")</td></tr>
    </table>`;

    const page2 = `
    <div class="page-break"></div>
    <div style="text-align:center;font-family:Arial;"><h1 style="margin:0;font-size:24px;color:#333;">&#9825; CARDIOVASCULAR CARE CENTER</h1><h3 style="margin:5px 0 15px 0;font-size:14px;">EXERCISE TESTING REPORT</h3></div>
    <table style="width:100%;font-size:11px;border:none;line-height:1.8;" cellpadding="0">
        <tr><td style="border:none;width:60%;">NAME: <u><b>${patientName}</b></u></td><td style="border:none;width:40%;">Age: <u><b>${v(tmt.age)}</b></u></td></tr>
        <tr><td style="border:none;" colspan="2">Date Performed: <u><b>${date}</b></u> &nbsp;&nbsp; Sex: <u><b>${echoSex || '&nbsp;&nbsp;&nbsp;&nbsp;'}</b></u> &nbsp;&nbsp; Weight: <u><b>${v(tmt.weight)}</b></u> &nbsp;&nbsp; Height: <u><b>${v(tmt.height)}</b></u> &nbsp;&nbsp; Date of Birth: <u><b>${v(tmt.birthday)}</b></u></td></tr>
        <tr><td style="border:none;">Requesting Physician: <u><b>${v(tmt.requesting_physician)}</b></u></td><td style="border:none;">Medications: <u><b>${v(tmt.medicines)}</b></u></td></tr>
        <tr><td style="border:none;" colspan="2" align="right">PMHR for AGE: <u style="display:inline-block;width:200px;text-align:left;"><b>${v(tmt.pmhr)}</b></u></td></tr>
    </table>
    <div style="font-size:11px;margin-top:10px;"><b>Protocol : BRUCE, MODIFIED BRUCE, NIH</b><br><b>Indication : <u>${v(tmt.indication) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></b></div>
    <table border="1" cellpadding="2" style="width:100%;border-collapse:collapse;font-size:10px;margin-top:10px;text-align:center;">
        <tr><th>Event</th><th>Speed<br>(mph)</th><th>Grade<br>(%)</th><th>METS</th><th>BP</th><th>HR</th><th>Remarks</th></tr>
        ${stagesHtml}
    </table>
    <div style="font-size:11px;margin-top:15px;">
        <p><b>INTERPRETATION:</b></p>
        <p>A. Resting ECG: <u style="font-weight:bold;">${v(tmt.resting_ecg) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
        <p>B. Response to Exercise: <u style="font-weight:bold;">${v(tmt.response_to_exercise) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
        <p>C. Exercise Capacity: <u style="font-weight:bold;">${v(tmt.exercise_capacity) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
        <p>D. Arrhythmias: <u style="font-weight:bold;">${v(tmt.arrhythmias) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
        <p>E. Probability of CAD: <u style="font-weight:bold;">${v(tmt.probability_cad) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
        <p style="margin-top:10px;"><b>CONCLUSION:</b> <u style="font-weight:bold;">${v(tmt.conclusion) || '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}</u></p>
    </div>
    <table style="width:100%;font-size:11px;margin-top:40px;border:none;">
        <tr><td style="width:60%;border:none;"></td><td style="width:40%;border-top:1px solid black;text-align:center;border-left:none;border-right:none;border-bottom:none;"><b>Cardiologist</b></td></tr>
    </table>`;

    return page1 + page2;
};

/**
 * Builds the full 2-page **Color Flow & Doppler Study** Word export body HTML.
 * Page 1: Doppler study table (Cardiovascular Care Center clinical format)
 * Page 2: Echocardiographic Report (delegates to buildEchoHTML)
 *
 * This is the canonical implementation — DopplerForm.vue delegates here.
 */
export const buildDopplerDocument = (
    dop: DopplerData,
    ec: EchoData,
    patientName: string,
    date: string,
): string => {
    const page1 = `
    <h3 style="text-align:center;color:#a1282c;font-family:Arial,sans-serif;font-size:16px;">COLOR FLOW &amp; DOPPLER STUDY</h3>
    <table border="1" cellpadding="2" style="width:100%;border-collapse:collapse;font-family:'Times New Roman',serif;font-size:10px;">
        <tr><td colspan="3"><b>Patient:</b> ${patientName}</td><td colspan="2"><b>File #:</b> ${v(ec.file_no)}</td><td colspan="2"><b>Date:</b> ${date}</td></tr>
        <tr class="bg-gray"><td colspan="3"><b>Mitral Valve</b></td><td style="text-align:center"><b>Normal Range<br>(M | F)</b></td><td colspan="3"><b>Aortic Valve</b></td></tr>
        <tr><td><b>Peak Velocity<br>(cm/sec)</b></td><td><b>E</b> ${v(dop.mv_peak_vel_e)}</td><td><b>A</b> ${v(dop.mv_peak_vel_a)}</td><td></td><td></td><td style="text-align:center"><b>BEFORE</b></td><td style="text-align:center"><b>AFTER</b></td></tr>
        <tr><td><b>Peak Gradient<br>(mmHg)</b></td><td><b>E</b> ${v(dop.mv_peak_grad_e)}</td><td><b>A</b> ${v(dop.mv_peak_grad_a)}</td><td></td><td><b>Peak velocity</b></td><td>${v(dop.av_peak_vel_before)} <b>cm/s</b></td><td>${v(dop.av_peak_vel_after)} <b>cm/s</b></td></tr>
        <tr><td colspan="3"><b>IVRT</b> ${v(dop.mv_ivrt)}</td><td><b>60 - 90 sec</b></td><td><b>Pressure Gradient</b></td><td>${v(dop.av_press_grad_before)} <b>mmHg</b></td><td>${v(dop.av_press_grad_after)} <b>mmHg</b></td></tr>
        <tr><td colspan="3"><b>Regurgitant Fraction</b> ${v(dop.mv_regurg_frac)}</td><td><b>&lt; 30 %</b></td><td><b>Area by Continuity equation</b></td><td colspan="2">${v(dop.av_area)} <b>&gt; 1.5 cm&sup2;</b></td></tr>
        <tr><td colspan="3"><b>MV DecTime</b> ${v(dop.mv_dectime)}</td><td><b>162 - 230 msec</b></td><td><b>Regurgitant Fraction</b></td><td colspan="2">${v(dop.av_regurg_frac)} <b>&lt; 30 %</b></td></tr>
        <tr><td colspan="3"><b>E/A ratio</b> ${v(dop.mv_ea_ratio)}</td><td></td><td><b>AR PHT</b></td><td colspan="2">${v(dop.av_ar_pht)} <b>&gt; 500 msec</b></td></tr>
        <tr><td colspan="3"><b>Area by PHT</b> ${v(dop.mv_area_pht)}</td><td><b>&gt; 2.0 cm&sup2;</b></td><td><b>AV Mean Gradient</b></td><td colspan="2">${v(dop.av_mean_grad)} <b>&lt; 20 mmHg</b></td></tr>
        <tr><td colspan="3"><b>Mean pressure gradient</b> ${v(dop.mv_mean_pressure)}</td><td><b>&lt; 5 mmHg</b></td><td><b>AR jet density</b></td>
            <td><span style="font-family:Arial;">${dop.av_ar_jet === 'Faint' ? '&#9746;' : '&#9744;'}</span> Faint</td>
            <td><span style="font-family:Arial;">${dop.av_ar_jet === 'Dense' ? '&#9746;' : '&#9744;'}</span> Dense</td>
        </tr>
        <tr class="bg-gray"><td colspan="4"><b>Tissue Doppler (cm/s)</b></td><td colspan="3"><b>Pulmonic valve</b></td></tr>
        <tr><td style="text-align:center"><b>Septal</b></td><td><b>e'</b> ${v(dop.td_septal_e)}</td><td><b>a'</b> ${v(dop.td_septal_a)}</td><td><b>E/e'</b> ${v(dop.td_septal_ee)}</td><td colspan="2"><b>Acceleration time</b></td><td>${v(dop.pv_accel_time)} <b>msec</b></td></tr>
        <tr><td style="text-align:center"><b>Lateral</b></td><td><b>e'</b> ${v(dop.td_lateral_e)}</td><td><b>a'</b> ${v(dop.td_lateral_a)}</td><td><b>E/e'</b> ${v(dop.td_lateral_ee)}</td><td colspan="2"><b>Pressure Gradient</b></td><td>${v(dop.pv_press_grad)} <b>mmHg</b></td></tr>
        <tr class="bg-gray"><td colspan="4"><b>Tricuspid valve</b></td><td colspan="2"><b>Regurgitation</b></td><td>${v(dop.pv_regurg)} <b>%</b></td></tr>
        <tr><td colspan="3"><b>E wave velocity</b> ${v(dop.tv_e_wave)}</td><td><b>cm/s 0.64</b></td><td colspan="2"><b>Systolic PAP by acceleration time</b></td><td>${v(dop.pv_sys_pap)} <b>&gt; 25 mmHg</b></td></tr>
        <tr><td colspan="3"><b>A wave velocity</b> ${v(dop.tv_a_wave)}</td><td><b>cm/s 1.65</b></td><td><b>PR jet density</b></td>
            <td><span style="font-family:Arial;">${dop.pv_pr_jet === 'Faint' ? '&#9746;' : '&#9744;'}</span> Faint</td>
            <td><span style="font-family:Arial;">${dop.pv_pr_jet === 'Dense' ? '&#9746;' : '&#9744;'}</span> Dense</td>
        </tr>
        <tr><td colspan="3"><b>Regurgitant Fraction</b> ${v(dop.tv_regurg_frac)}</td><td><b>%</b></td><td colspan="2"><b>RVOT VTI</b></td><td>${v(dop.pv_rvot_vti)} <b>cm</b></td></tr>
        <tr><td colspan="3"><b>Peak TR jet velocity</b> ${v(dop.tv_peak_tr)}</td><td><b>cm/s</b></td><td colspan="3"></td></tr>
        <tr><td colspan="3"><b>Systolic PAP by TR jet</b> ${v(dop.tv_sys_pap)}</td><td><b>&gt; 25 mmHg</b></td>
            <td colspan="3"><b>Qp/Qs = </b> ${v(dop.qp_qs)} <span style="font-size:8px;float:right;">Normal: 1.0<br>Lt to Rt: &gt; 1.0<br>Rt to Lt: &lt; 1.0</span></td>
        </tr>
    </table>
    <p style="margin-top:15px;"><b>CONCLUSION :</b> ${v(dop.conclusion)}</p>
    <table class="no-border" style="margin-top:40px;">
        <tr><td style="width:60%;"></td><td style="width:40%;text-align:center;border-top:1px solid black;padding-top:4px;"><b>Cardiologist</b></td></tr>
    </table>`;

    const page2 =
        `<div class="page-break"></div>` + buildEchoHTML(ec, patientName, date);

    return page1 + page2;
};

/**
 * Builds the **Manual Draft** Word export body HTML.
 * Page 1: draft header + title, the attached scans (embedded via their public
 * storage URL), the drafted text and conclusion.
 */
export const buildManualDraftDocument = (
    draft: ManualDraftData,
    patientName: string,
    date: string,
    doctorName?: string,
): string => {
    const imagesHtml = (draft.images ?? [])
        .map(
            (img) => `
        <tr>
            <td style="text-align:center;padding:4px;">
                <img src="${v(img.url)}" alt="${v(
                    img.name,
                )}" style="max-width:5in;max-height:3in;">
                <br><span style="font-size:7pt;color:#555;">${v(img.name)}</span>
            </td>
        </tr>`,
        )
        .join('');

    return `
<h2>Manual Draft</h2>
<table class="no-border">
  <tr>
    <td style="border:none;"><b>Patient Name:</b> ${patientName}</td>
    <td style="border:none;"><b>Date:</b> ${date}</td>
  </tr>
  <tr>
    <td style="border:none;" colspan="2"><b>Requesting Physician:</b> ${v(
        draft.requesting_physician,
    )}</td>
  </tr>
  ${
      v(draft.title)
          ? `<tr><td style="border:none;" colspan="2"><b>Title:</b> ${v(
                draft.title,
            )}</td></tr>`
          : ''
  }
</table>
${
    (draft.images ?? []).length > 0
        ? `<h3>Attached Scans</h3>
<table style="width:100%;border:none;">${imagesHtml}</table>`
        : ''
}
<h3>Draft</h3>
<p style="white-space:pre-wrap;min-height:60px;">${v(draft.draft)}</p>
${
    v(draft.conclusion)
        ? `<h3>Conclusion</h3>
<p style="white-space:pre-wrap;">${v(draft.conclusion)}</p>`
        : ''
}
${signatureBlock(doctorName)}`;
};
