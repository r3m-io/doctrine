{{R3M}}
{{$response = Package.R3m.Io.Doctrine:Main:table.column.all(flags(), options())}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

