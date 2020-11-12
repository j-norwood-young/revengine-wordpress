$(function() {
    const articles = revengine_content_promoter_vars.articles;
    const table = $(".front_featured_post");
    table.find("thead > tr").append(`<th scope="row" id="vh" class="manage-column column-vh">Hits</th>`);
    table.find("tbody > tr").append(`<td class="column-vh" data-colname="hits">-</td>`);
    for (let article of articles) {
        // Find checkbox
        const checkbox = table.find(`input[type=checkbox][value="${article.post_id }"]`);
        // Extrapolate row
        const row = checkbox.parents("tr");
        // Add column
        row.find(".column-vh").html(article.hits);
    }
});