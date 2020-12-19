<?php
    header('Content-Type: text/javascript');
?>
var tp = window["tp"] || [];
tp.push(["setContentCreated", "<?= $_GET["date_published"] ?>"]);
tp.push(["setContentAuthor", "<?= $_GET["author"] ?>"]);
tp.push(["setContentSection", "<?= $_GET["primary_section"] ?>"]);
tp.push(["setTags", "<?= $_GET["tags"] ?>"]);
tp.push(["setCustomParam", "type", "premium", "content"])
tp.push(["setCustomVariable", "logged_in", "<?= ($_GET["logged_in"]) ? "true" : "false" ?>"]);
tp.push(["setCustomVariable", "memberships", "<?= $_GET["memberships"] ?>"]);
(function(src){
    var a=document.createElement("script");
    a.type="text/javascript";
    a.async=true;
    a.src=src;
    var b=document.getElementsByTagName("script")[0];
    b.parentNode.insertBefore(a,b)
})(`//<?= ($_GET["revengine_piano_sandbox_mode"]) ? "sandbox.tinypass.com" : "experience.tinypass.com" ?>/xbuilder/experience/load?aid=<?= $_GET["revengine_piano_id"] ?>`);