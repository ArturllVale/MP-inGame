$('a').on('click', function (e) {
    e.preventDefault();
    let vl = $("#valor").val();

    // Validar se o valor foi inserido
    if (!vl || vl <= 0) {
        alert("Por favor, insira um valor válido para doação!");
        return false;
    }

    let link = $(this).attr('href');
    location.href = link + '?vl=' + vl;
});
