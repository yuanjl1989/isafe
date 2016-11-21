function docancel(safe_id) {

    if(confirm('你确定要取消该申请么？')){
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {},
            url: "/site/cancel?id="+safe_id,
            success: function () {
            }
        });
        sleep(300);
        window.location.reload();
    }
}

function sleep(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime)    return;
    }
}


