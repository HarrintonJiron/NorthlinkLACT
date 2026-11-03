<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
  <Worksheet ss:Name="Reporte"><Table>
    <Row><Cell><Data ss:Type="String">NORTHLINK QUEBAR</Data></Cell></Row>
    <Row><Cell><Data ss:Type="String">{{ $data['title'] }}</Data></Cell></Row>
    <Row><Cell><Data ss:Type="String">Periodo {{ $data['from'] }} a {{ $data['to'] }}</Data></Cell></Row>
    <Row />
    <Row>@foreach($data['columns'] as $column)<Cell><Data ss:Type="String">{{ $column }}</Data></Cell>@endforeach</Row>
    @foreach($data['rows'] as $row)<Row>@foreach($row as $cell)<Cell><Data ss:Type="String">{{ $cell }}</Data></Cell>@endforeach</Row>@endforeach
    <Row />
    @foreach($data['totals'] as $label => $value)<Row><Cell><Data ss:Type="String">{{ $label }}</Data></Cell><Cell><Data ss:Type="String">{{ $value }}</Data></Cell></Row>@endforeach
  </Table></Worksheet>
</Workbook>
