<script>
    var url = "<?php echo esc_url($url) ?>";
    var data = <?php echo json_encode($data) ?>;
    var xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log(xhr.status);
            console.log(xhr.responseText);
        }
    };
    xhr.onerror = function() {
        console.log("Error sending request");
    };
    xhr.withCredentials = true;
    xhr.send(JSON.stringify(data));
</script>
<noscript><iframe src="<?php echo esc_url($url) ?>" title="revengine-tracker" frameborder="0" style="width:0;height:0;border:0;border:none;position:absolute" data-ampdevmode="data-ampdevmode"></iframe></noscript>