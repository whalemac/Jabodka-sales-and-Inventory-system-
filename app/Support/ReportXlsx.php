<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dependency-free XLSX writer (inline strings, no shared-strings table).
 *
 * Sheet layout:
 *   Row 1 — report title (bold, merged across the used columns)
 *   Row 2 — period / generated-on line (gray)
 *   Row 3 — blank
 *   Row 4 — header row (white-on-navy, filled)
 *   Row 5…— data rows
 *
 * Column number formats are declared per column index (0-based):
 *   0 => 'money'  → #,##0.00
 *   0 => 'int'    → #,##0
 */
class ReportXlsx
{
    public static function stream(string $filename, array $sheet): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheet) {
            $path = tempnam(sys_get_temp_dir(), 'jabodka_xlsx_');

            $zip = new \ZipArchive();
            if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                abort(500, 'Could not create the spreadsheet file.');
            }

            $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
            $zip->addFromString('_rels/.rels', self::relsXml());
            $zip->addFromString('xl/workbook.xml', self::workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
            $zip->addFromString('xl/styles.xml', self::stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($sheet));

            $zip->close();

            readfile($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── Sheet XML ────────────────────────────────────────────────────────────

    private static function sheetXml(array $sheet): string
    {
        $title   = (string) ($sheet['title'] ?? 'Report');
        $period  = (string) ($sheet['period'] ?? '');
        $headers = array_values($sheet['headers'] ?? []);
        $rows    = array_values($sheet['rows'] ?? []);
        $formats = $sheet['formats'] ?? [];      // col index (0-based) => 'money'|'int'
        $boldRows = $sheet['boldRows'] ?? [];    // 0-based index into $rows
        $sections = $sheet['sections'] ?? [];    // extra blocks below the main table

        $colCount = max(count($headers), 0);
        foreach ($rows as $row) {
            $colCount = max($colCount, count($row));
        }
        foreach ($sections as $section) {
            $colCount = max($colCount, count($section['headers'] ?? []));
            foreach (($section['rows'] ?? []) as $row) {
                $colCount = max($colCount, count($row));
            }
        }
        $colCount = max($colCount, 1);

        $styleForCol = [];
        foreach ($formats as $col => $kind) {
            $styleForCol[(int) $col] = $kind === 'int' ? 5 : 4;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'."\n";

        // Column widths: size the first columns wider for labels/reasons.
        $xml .= '<cols>';
        for ($c = 1; $c <= $colCount; $c++) {
            $width = $c === 1 ? 16 : ($c <= 3 ? 18 : 20);
            $xml .= '<col min="'.$c.'" max="'.$c.'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols>'."\n";

        $xml .= '<sheetData>'."\n";

        // Row 1 — title
        $xml .= '<row r="1">'.self::stringCell('A1', $title, 1).'</row>'."\n";

        // Row 2 — period line
        if ($period !== '') {
            $xml .= '<row r="2">'.self::stringCell('A2', $period, 2).'</row>'."\n";
        }

        // Row 3 — blank spacer
        $xml .= '<row r="3"/>'."\n";

        // Row 4 — headers
        $xml .= '<row r="4">';
        foreach ($headers as $i => $header) {
            $xml .= self::stringCell(self::columnName($i).'4', (string) $header, 3);
        }
        $xml .= '</row>'."\n";

        // Data rows
        $excelRow = 5;
        $excelRow = self::dataRowsXml($xml, $rows, $excelRow, $styleForCol, $boldRows);

        // Extra sections (blank spacer + bold title + header row + data rows)
        foreach ($sections as $section) {
            $sectionStyles = [];
            foreach (($section['formats'] ?? []) as $col => $kind) {
                $sectionStyles[(int) $col] = $kind === 'int' ? 5 : 4;
            }
            if ($sectionStyles === []) {
                $sectionStyles = $styleForCol;
            }

            $excelRow++;                                   // blank spacer row
            $xml .= '<row r="'.$excelRow.'">'
                .self::stringCell('A'.$excelRow, (string) ($section['title'] ?? ''), 1)
                .'</row>'."\n";
            $excelRow++;

            $xml .= '<row r="'.$excelRow.'">';
            foreach (array_values($section['headers'] ?? []) as $i => $header) {
                $xml .= self::stringCell(self::columnName($i).$excelRow, (string) $header, 3);
            }
            $xml .= '</row>'."\n";
            $excelRow++;

            $excelRow = self::dataRowsXml(
                $xml,
                array_values($section['rows'] ?? []),
                $excelRow,
                $sectionStyles,
                $section['boldRows'] ?? []
            );
        }

        $xml .= '</sheetData>'."\n";

        if ($colCount > 1) {
            $xml .= '<mergeCells count="1"><mergeCell ref="A1:'.self::columnName($colCount - 1).'1"/></mergeCells>'."\n";
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * Append data rows starting at $excelRow; returns the next free row number.
     */
    private static function dataRowsXml(string &$xml, array $rows, int $excelRow, array $styleForCol, array $boldRows): int
    {
        foreach ($rows as $r => $row) {
            $isBold = in_array($r, $boldRows, true);

            $xml .= '<row r="'.$excelRow.'">';
            foreach (array_values($row) as $c => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $ref = self::columnName($c).$excelRow;
                $style = $styleForCol[$c] ?? 0;
                if ($isBold && $style === 0) {
                    $style = 1;
                }

                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$value.'</v></c>';
                } else {
                    $xml .= self::stringCell($ref, (string) $value, $style);
                }
            }
            $xml .= '</row>'."\n";

            $excelRow++;
        }

        return $excelRow;
    }

    private static function stringCell(string $ref, string $value, int $style): string
    {
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
    }

    private static function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    // ─── Package parts ────────────────────────────────────────────────────────

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'                                             // 0 — default
            .'<font><b/><sz val="14"/><name val="Calibri"/></font>'                                         // 1 — title
            .'<font><sz val="10"/><color rgb="FF808080"/><name val="Calibri"/></font>'                      // 2 — meta
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'                   // 3 — header
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/><bgColor indexed="64"/></patternFill></fill>' // 2 — header navy
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="6">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                          // 0 — default
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                            // 1 — title bold
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                            // 2 — meta gray
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'              // 3 — header
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                    // 4 — #,##0.00
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                    // 5 — #,##0
            .'</cellXfs>'
            .'</styleSheet>';
    }
}
