wordpress-seotdk
================

A wordpress plugin for page seoo by modify title keywords and description.

一款通过修改网页的title keywords description标签来实现SEO功能的WordPress插件。
使用方法在.doc文件中有图文详解。

安装方法：
1.使用FTP连接到你的网站空间；
2.把wp-seo-tdk.php上传到你的WordPress插件目录/wp-content/plugins/下面；
3.进入WordPress后台，启用这个插件

接下来进入“插件-SEO TDK”选项进行初始化设置。
这个设置页面会告诉你，分类、文章页也可以使用SEO标题和关键词等。

注意：要使该插件生效，必须满足以下条件：
1.你的WordPress主题中使用<title><?php wp_title(''); ?></title>来输出网页标题；
2.使用wp_head()来输出头部信息，关键词和描述meta标签靠它来实现。