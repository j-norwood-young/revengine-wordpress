var tp = window["tp"] || [];
tp.push(["setContentCreated", revengine_piano_composer_vars.date_published]);
tp.push(["setContentAuthor", revengine_piano_composer_vars.author]);
tp.push(["setContentSection", revengine_piano_composer_vars.sections]);
tp.push(["setTags", revengine_piano_composer_vars.tags]);
tp.push(["setCustomParam", "type", "premium", "content"])
tp.push(["setCustomVariable", "logged_in", String(!!(revengine_piano_composer_vars.logged_in))]);
tp.push(["setCustomVariable", "memberships", revengine_piano_composer_vars.memberships]);
(function(src){
    var a=document.createElement("script");
    a.type="text/javascript";
    a.async=true;
    a.src=src;
    var b=document.getElementsByTagName("script")[0];
    b.parentNode.insertBefore(a,b)
})(`https://${(revengine_piano_composer_vars.revengine_piano_sandbox_mode) ? `sandbox.tinypass.com` : `experience.tinypass.com`}/xbuilder/experience/load?aid=${revengine_piano_composer_vars.revengine_piano_id }`);