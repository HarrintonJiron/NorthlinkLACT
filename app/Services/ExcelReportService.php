<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ExcelReportService
{
    public function build(array $data): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'northlink-xlsx-');
        if ($temporaryPath === false) {
            throw new RuntimeException('No se pudo preparar el archivo Excel.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $columnCount = max(1, count($data['columns']));
        $lastColumn = $this->columnName($columnCount);
        $headerRow = 5;
        $lastDataRow = $headerRow + max(1, count($data['rows']));
        $tableRange = "A{$headerRow}:{$lastColumn}{$lastDataRow}";

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('docProps/app.xml', $this->appProperties());
        $zip->addFromString('docProps/core.xml', $this->coreProperties($data['title']));
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($data, $lastColumn, $tableRange));
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->worksheetRelationships());
        $zip->addFromString('xl/tables/table1.xml', $this->table($data['columns'], $tableRange));
        $zip->close();

        $contents = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el archivo Excel generado.');
        }

        return $contents;
    }

    private function worksheet(array $data, string $lastColumn, string $tableRange): string
    {
        $rows = [];
        $rows[] = $this->row(1, [['value' => 'NORTHLINK QUEBAR', 'style' => 1]]);
        $rows[] = $this->row(2, [['value' => $data['title'], 'style' => 2]]);
        $rows[] = $this->row(3, [['value' => "Periodo {$data['from']} a {$data['to']} · Generado ".now()->format('d/m/Y H:i'), 'style' => 3]]);
        $rows[] = '<row r="4"/>';
        $rows[] = $this->row(5, array_map(fn (string $column): array => ['value' => $column, 'style' => 4], $data['columns']));

        if ($data['rows'] === []) {
            $rows[] = $this->row(6, [['value' => 'Sin registros para el periodo seleccionado', 'style' => 3]]);
        } else {
            foreach ($data['rows'] as $index => $reportRow) {
                $rows[] = $this->row(6 + $index, array_map(fn ($value): array => [
                    'value' => $value,
                    'style' => is_numeric($value) ? 5 : 0,
                ], $reportRow));
            }
        }

        $summaryStart = 7 + count($data['rows']);
        foreach (array_values($data['totals']) as $index => $value) {
            $label = array_keys($data['totals'])[$index];
            $rows[] = $this->row($summaryStart + $index, [
                ['value' => $label, 'style' => 6],
                ['value' => $value, 'style' => is_numeric($value) ? 7 : 6],
            ]);
        }

        $columns = '';
        foreach ($data['columns'] as $index => $column) {
            $width = min(42, max(12, mb_strlen((string) $column) + 4));
            $position = $index + 1;
            $columns .= "<col min=\"{$position}\" max=\"{$position}\" width=\"{$width}\" customWidth=\"1\"/>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="5" topLeftCell="A6" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/><cols>'.$columns.'</cols><sheetData>'.implode('', $rows).'</sheetData>'
            ."<mergeCells count=\"3\"><mergeCell ref=\"A1:{$lastColumn}1\"/><mergeCell ref=\"A2:{$lastColumn}2\"/><mergeCell ref=\"A3:{$lastColumn}3\"/></mergeCells>"
            ."<autoFilter ref=\"{$tableRange}\"/><pageMargins left=\"0.25\" right=\"0.25\" top=\"0.5\" bottom=\"0.5\" header=\"0.2\" footer=\"0.2\"/>"
            .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/><tableParts count="1"><tablePart r:id="rId1"/></tableParts></worksheet>';
    }

    private function row(int $number, array $cells): string
    {
        $xml = "<row r=\"{$number}\">";
        foreach ($cells as $index => $cell) {
            $reference = $this->columnName($index + 1).$number;
            $style = (int) ($cell['style'] ?? 0);
            $value = $cell['value'];
            if (is_numeric($value) && $value !== '') {
                $xml .= "<c r=\"{$reference}\" s=\"{$style}\"><v>".(float) $value.'</v></c>';
            } else {
                $xml .= "<c r=\"{$reference}\" s=\"{$style}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">".$this->escape((string) ($value ?? '')).'</t></is></c>';
            }
        }

        return $xml.'</row>';
    }

    private function table(array $columns, string $range): string
    {
        $tableColumns = '';
        foreach (array_values($columns) as $index => $column) {
            $tableColumns .= '<tableColumn id="'.($index + 1).'" name="'.$this->escape($column).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<table xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" id="1" name="ReporteNorthlink" displayName="ReporteNorthlink" ref="'.$range.'" totalsRowShown="0">'
            .'<autoFilter ref="'.$range.'"/><tableColumns count="'.count($columns).'">'.$tableColumns.'</tableColumns>'
            .'<tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0"/></table>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4"><font><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="16"/><name val="Arial"/></font><font><b/><color rgb="FF173F35"/><sz val="13"/><name val="Arial"/></font><font><i/><color rgb="FF64748B"/><sz val="9"/><name val="Arial"/></font></fonts>'
            .'<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF173F35"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFDDEFE8"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/><xf numFmtId="4" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function worksheetRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml"/></Relationships>';
    }

    private function coreProperties(string $title): string
    {
        $created = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>'.$this->escape($title).'</dc:title><dc:creator>Northlink Quebar</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created></cp:coreProperties>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Northlink LACT</Application></Properties>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
