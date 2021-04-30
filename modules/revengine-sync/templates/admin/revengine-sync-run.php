<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous"></script>
<style>
    #progress_bar {
        width: 100%;
        background-color: grey;
    }
    #progress_bar_bar {
        width: 0%;
        height: 30px;
        background-color: #4CAF50;
        text-align: center; /* To center it horizontally (if you want) */
        line-height: 30px; /* To center it vertically */
        color: white;
    }
    #progress_txt {
        padding-left: 10px;
    }
</style>
<div class="wrap">
    <h2>Sync Users with RevEngine Readers</h2>
    <p><strong>Number of unsynced readers in RevEngine:</strong> <span id="reader_count"></span></p>
    <p><strong>Page:</strong> <span id="pages"></span></p>
    <p><strong>Estimated Time:</strong> <span id="est_time"></span></p>
    <p>
        <div id="progress_bar">
            <div id="progress_bar_bar">
                <div id="progress_txt"></div>
            </div>
        </div>
    </p>
</div>