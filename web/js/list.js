function docancel(safe_id) {

    if(confirm('你确定要取消该申请么？')){
        window.location.reload();
        $.ajax({
            type: "POST",
            dataType: "json",
            data: {},
            url: "/site/cancel?id="+safe_id,
            success: function () {
            }
        });
    }
}

$('#new_safe').on('click', function () {
    layer.confirm('', {
        title: false,
        content: '请选择扫描工具并前往',
        closeBtn: 0,
        shadeClose: 1,
        btn: ['WVS -> Go','Appscan -> Go'] //按钮
    }, function () {
        self.location='/new/new';
    }, function () {
        self.location='/new/new?tool=appscan';
    });
});


