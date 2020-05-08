(function(src){
    var a=document.createElement("script");
    a.type="text/javascript";
    a.async=true;
    a.src=src;
    var b=document.getElementsByTagName("script")[0];
    b.parentNode.insertBefore(a,b)
})(`https://${(revengine_piano_composer_vars.revengine_piano_sandbox_mode) ? `sandbox.tinypass.com` : `api.tinypass.com`}/xbuilder/experience/load?aid=${revengine_piano_composer_vars.revengine_piano_id }`);