commands文件夹各文件解释：
1、rsync.bat批处理用来同步本地的文件至平台所在服务器
2、scantask.bat、scantask_1.bat、scantask_2.bat批处理是为了window定时任务所创建，内容皆一样，为不同时间段调用所建。目的为：在上一个任务没有结束的情况下正常开始下一个任务。
3、TaskWithoutYii.php为主文件，直接执行生成报告
4、AnalisysReport.php为报告解析文件，解析wvs、appscan生成的报告并生成新的组合报告
5、LCS.php\Translate.php为谷歌翻译，用于翻译wvs部分issue
6、log文件夹存放扫描定时任务的调用日志


---------------------------------------------------------
scanreport文件夹
存在各扫描任务的报告信息、日志信息等