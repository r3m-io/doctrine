{{R3M}}
{{$response = Package.R3m.Io.Doctrine:Main:table.index.all(flags(), options())}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

