{{R3M}}
{{$register = Package.R3m.Io.Import:Init:register()}}
{{if(!is.empty($register))}}
{{Package.R3m.Io.Doctrine:Import:role.system()}}
{{$options = options()}}
{{Package.R3m.Io.Server:Main:system.config($options)}}
{{/if}}