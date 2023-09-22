<script>
    var url = "<?php echo esc_url($url) ?>";
    var xhr = new XMLHttpRequest();
    console.log({ url });
    xhr.open("GET", url, true);
    xhr.withCredentials = true;
    xhr.send();
</script>
<noscript><iframe src="<?php echo esc_url($url) ?>" title="revengine-tracker" frameborder="0" style="width:0;height:0;border:0;border:none;position:absolute" data-ampdevmode="data-ampdevmode"></iframe></noscript>