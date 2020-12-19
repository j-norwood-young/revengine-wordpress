<script data-ampdevmode data-amp-plus-gam>
    var tp = window["tp"] || [];
    tp.push(["setContentCreated", <?= $post["date_published"] ?? '""' ?>]);
    tp.push(["setContentAuthor", <?= $post["author"] ?? '""' ?>]);
    tp.push(["setContentSection", <?= $post["primary_section"] ?? '""' ?>]);
    tp.push(["setTags", <?= $post["tags"] ?? '""' ?>]);
    tp.push(["setCustomParam", "type", "premium", "content"])
    tp.push(["setCustomVariable", "logged_in", "<?= ($user["logged_in"]) ? "true" : "false" ?>"]);
    tp.push(["setCustomVariable", "memberships", <?= $user["memberships"] ?? '""' ?>]);
    (function(src){
        var a=document.createElement("script");
        a.type="text/javascript";
        a.async=true;
        a.src=src;
        var b=document.getElementsByTagName("script")[0];
        b.parentNode.insertBefore(a,b)
    })(`//<?= ($options["revengine_piano_sandbox_mode"]) ? "sandbox.tinypass.com" : "experience.tinypass.com" ?>/xbuilder/experience/load?aid=<?= $options["revengine_piano_id"] ?>`);
</script>