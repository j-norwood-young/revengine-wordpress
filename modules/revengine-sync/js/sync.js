const api_url = revengine_piano_sync_vars.api_url;
const api_key = revengine_piano_sync_vars.api_key;
const revengine_api_key = revengine_piano_sync_vars.revengine_api_key;
const PER_PAGE = 100;
const time_started = new Date();
if (!$) $ = jQuery;

const formatNumber = (n) => {
    return new Intl.NumberFormat().format(n);
}

const readerCount = async () => {
    try {
        return (await $.get(`${api_url}/count/reader?filter[wordpress_id]=$exists:false&filter[test_wordpress_id]=$exists:false&apikey=${api_key}`)).count;
    } catch(err) {
        return Promise.reject(err);
    }
}

const getReaders = async (page) => {
    try {
        const req = {
            url: `/wp-json/revengine/v1/sync_users?page=${page}&per_page=${ PER_PAGE }`,
            type: "GET",
            beforeSend: function (xhr) {
                xhr.setRequestHeader('Authorization', `Bearer ${ revengine_api_key }`);
            },
            data: {},
        }
        const result = await $.ajax(req);
        return result;
    } catch(err) {
        return Promise.reject(err);
    }
}

const showProgress = (page, per_page, count) => {
    const total_pages = Math.ceil(count / per_page);
    $("#pages").html(`${ formatNumber(page) } / ${ formatNumber(total_pages) }`);
    $("#est_time").html(`${ moment.duration((new Date() - time_started) / page * total_pages / 1000, "seconds").humanize() }`);
    $("#progress_bar_bar").css("width", `${page / total_pages * 100}%`)
    $("#progress_txt").html(`${Math.round(page / total_pages * 100)}%`)
}

const runSync = async() => {
    try {
        const count = await readerCount();
        const total_pages = Math.ceil(count / PER_PAGE);
        $("#reader_count").html(formatNumber(count));
        showProgress(0, PER_PAGE, count);
        for (let x = 0; x < total_pages; x++) {
            try {
                const readers = await getReaders(x);
            } catch(err) {
                console.error(err);
            }
            showProgress((x + 1), PER_PAGE, count);
            // console.log(readers);
        }
    } catch(err) {
        console.error(err);
        alert("Oh dear, something has gone wrong...");
    }
}

$(function() {
    runSync();
})