{{R3M}}
{{$register = Package.R3m.Io.Doctrine:Init:register()}}
{{if(!is.empty($register))}}
{{Package.R3m.Io.Doctrine:Import:role.system()}}
{{$options = options()}}
{{Package.R3m.Io.Doctrine:Main:system.config($options)}}
{{/if}}