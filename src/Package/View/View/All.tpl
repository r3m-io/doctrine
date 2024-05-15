{{R3M}}
{{$response = Package.R3m.Io.Doctrine:Main:view.all(flags(), options())}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

