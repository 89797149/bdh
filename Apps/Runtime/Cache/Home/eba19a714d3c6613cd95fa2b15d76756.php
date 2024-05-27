<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="Chrome=1,IE=edge" />
    <meta name="baidu-site-verification" content="fchvDjhZJD" />
    <script src="/Apps/Home/View/pc/royalslider/jquery-1.8.3.min.js"></script>
    <script src="/Apps/Home/View/pc/royalslider/jquery.royalslider.min.js?v=9.3.6"></script>
    <title>加入我们 </title>
    <meta name="keywords" content="加入我们">
    <meta name="description"
        content="买立得商家网采用线上线下结合,附近商家供货,售后的模式,打造一个线上线下结合的新零售模式，公司网点覆盖整个常州地区,全城按需配送“当日达”,产品覆盖电脑,手机数码,办公设备,电脑配件,办公文具,劳保用品,租赁维修,上门服务等.">
    <meta name="apple-touch-fullscreen" content="YES" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="format-detection" content="telephone=no" />

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
    <meta name="apple-touch-fullscreen" content="YES" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="format-detection" content="telephone=no" />
    <link type="image/x-icon" rel="shortcut icon" href="/<?php echo $config['mallLogo'];?>" />
    <link rel="stylesheet" href="/Apps/Home/View/pc/css/style.css" data-version="@VERSION@">
    <link rel="stylesheet" href="/Apps/Home/View/pc/css/aboutus.css">
    <!--<link rel="stylesheet" href="-->
    <!--/css/responsive.css">-->
</head>


<style>
    /* ***************************************************************** */
    /* 320----767px 分辨率 */

    /* header Start */
    #full-width-slider {
        width: 100%;
        color: #000;
    }

    .coloredBlock {
        padding: 12px;
        background: rgba(255, 0, 0, 0.6);
        color: #FFF;
        width: 200px;
        left: 20%;
        top: 5%;
    }

    .infoBlock {
        position: absolute;
        top: 30px;
        right: 30px;
        left: auto;
        max-width: 25%;
        padding-bottom: 0;
        background: #FFF;
        background: rgba(255, 255, 255, 0.8);
        overflow: hidden;
        padding: 20px;
    }

    .infoBlockLeftBlack {
        color: #FFF;
        background: #000;
        background: rgba(0, 0, 0, 0.75);
        left: 30px;
        right: auto;
    }

    .infoBlock h4 {
        font-size: 20px;
        line-height: 1.2;
        margin: 0;
        padding-bottom: 3px;
    }

    .infoBlock p {
        font-size: 14px;
        margin: 4px 0 0;
    }

    .infoBlock a {
        color: #FFF;
        text-decoration: underline;
    }

    .photosBy {
        position: absolute;
        line-height: 24px;
        font-size: 12px;
        background: #FFF;
        color: #000;
        padding: 0px 10px;
        position: absolute;
        left: 12px;
        bottom: 12px;
        top: auto;
        border-radius: 2px;
        z-index: 25;
    }

    .photosBy a {
        color: #000;
    }

    .fullWidth {
        max-width: 1400px;
        margin: 0 auto 24px;
    }


    #footer {
        position: absolute;
        left: 0;
        right: 0;
        /*bottom: 0;*/
    }

    @media only screen and (max-width: 767px) and (min-width: 500px) {
        .ipone_nav_icon {
            width: 8%;
            margin-top: 10px !important;
            margin-right: 10px;
            float: right;
        }
    }



    @media only screen and (min-width: 320px) and (max-width: 767px) {

        .container,
        #header,
        #main {
            width: 95% !important;
            font-size: 20px;
        }

        #header .logo {
            background-size: 80% !important;
            margin-top: 19px;
            position: fixed;
            top: 0;
            z-index: 2;
        }


        .ipone_nav_icon_box {
            display: block;
            position: fixed;
            top: 0;
            z-index: 1;
            width: 100%;
            left: 0;
            background: #fff;
            height: 75px;
        }

        /* header Start */
        #header .nav {
            display: none !important;
        }

        .ipone_navList {
            display: none;
        }

        .aboutus {
            text-align: left !important;
        }

        /* 320-767 显示导航 */

        .mark {
            background: rgba(0, 0, 0, 0.3) !important;
            position: fixed;
            width: 100%;
            height: 100%;
            left: 0;
            right: 0;
            z-index: 40;
            bottom: 0;
            top: 75px;
        }

        .ipone_navList_info {
            /* transition: all .3s linear; */
            font-size: 15px;
            display: none;
            position: fixed;
            top: 90px;
            right: 6px;
            z-index: 41;
            height: auto;
            padding: 0 25px;
            background: #fff;
            border-radius: 4px;
        }

        .ipone_nav_icon {
            width: 8%;
            margin-top: 25px;
            margin-right: 10px;
            float: right;
        }


        .ipone_navList_info ul li {
            padding: 10px 0;
            /* display: none; */
        }

        .triangle {
            position: fixed;
            top: 82px;
            z-index: 41;
            background: #fff;
            width: 15px;
            height: 15px;
            right: 26px;
            filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
            -moz-transform: rotate(135deg);
            -o-transform: rotate(135deg);
            -webkit-transform: rotate(135deg);
            transform: rotate(135deg)
        }

        .ipone_navList_info_icon_ul {
            margin: 0;
        }

        .ipone_navList_info_icon {
            width: 20px;
            height: auto;
            display: block;
            float: left;
            padding: 0 10px;
        }

        /* header END */

        .heroSlider .rsOverflow,
        .royalSlider.heroSlider {
            height: 520px !important;
        }

        .server img {
            display: block;
            width: 97%;
            height: auto;
            margin: 0 auto;
        }

        .news .clear {
            width: 70% !important;
            margin: 0 0 12px 33px;
        }

        .all-news {
            margin-left: 33px;
        }

        .news h2 img {
            width: 45%;
            height: auto;
            margin: 0 0 0 10px;
        }


    }



    @media screen and (min-width:768px) and (max-width:999999px) {

        #header .nav {
            display: block !important;
        }

        #header .ipone_nav {
            display: none;
        }

        .ipone_nav_icon_box {
            display: none;
        }

        .ipone_navList_info {
            display: none;
        }
    }
</style>

<body>
    <div id="page">
        <div id="header" class="container">
            <div class="logo float" style="background-image: url(/<?php echo $config['mallLogo'];?>)">
                <a href="/"> </a>
            </div>

            <div class="ipone_nav_icon_box">
                <!-- 菜单图标 -->
                <img class="ipone_nav_icon" src="/Apps/Home/View/pc/images/nav_icon.png" alt="">
            </div>


            <div class="nav floatR">
                <ul>
                    <li class="first active"><a href="/">首页</a></li>
                    <li class=" "><a href="<?php echo U('index/aboutus');?>">关于我们</a></li>
                    <li class=""><a href="<?php echo U('index/ourwork');?>">快乐工作</a></li>
                    <li class="last "><a href="<?php echo U('index/talent');?>">加入我们</a></li>
                </ul>
            </div>


            <!-- 分辨率小于 767px 展示 -->
            <div class="ipone_nav">
                <!-- 菜单列表 -->
                <div class="ipone_navList">
                    <!-- 遮罩层 -->
                    <div class="mark"></div>
                    <!-- 小三角 -->
                    <div class="triangle"></div>
                    <!-- 列表 -->
                    <div class="ipone_navList_info">
                        <ul class="ipone_navList_info_icon_ul">
                            <li class="first active">
                                <img class="ipone_navList_info_icon" src="/Apps/Home/View/pc/images/home_page.png"
                                    alt="">
                                <a href="/">首页</a></li>
                            <li class=" ">
                                <img class="ipone_navList_info_icon" src="/Apps/Home/View/pc/images/about_us.png"
                                    alt="">
                                <a href="<?php echo U('index/aboutus');?>">关于我们</a></li>
                            <li class="">
                                <img class="ipone_navList_info_icon" src="/Apps/Home/View/pc/images/happy_work.png"
                                    alt="">
                                <a href="<?php echo U('index/ourwork');?>">快乐工作</a></li>
                            <li class="last ">

                                <img class="ipone_navList_info_icon" src="/Apps/Home/View/pc/images/join_us.png" alt="">
                                <a href="<?php echo U('index/talent');?>">加入我们</a></li>
                        </ul>
                    </div>
                </div>
            </div>



            <div class="clear"></div>
        </div>

        <div id="content">
            <div id="main">
                <!--<div class="title">
                        <div class="clear"></div>
                    </div>
                    <div class="talent kuang">
                        <div class="mrd"></div>
                        <div class="mrd_title">
                            <span>目标明确</span>
                            <span>人人参与</span>
                            <span>担当责任</span>
                        </div>
                        <div class="mrd_content">
                            <div class="description">
                                <p>我们目标明确：</p>
                                <p>
                                    不忘使命，将可依赖、有温度的服务送达至千家万户。<br />
                                    不断检视，在前进道路上坚持最初的梦想，成为幸福生活的建设者。
                                </p>
                                <br />
                                <p>既人人参与，又担当责任：</p>
                                <p>
                                    在这里，你不是一颗普通的螺丝钉，而是主动创新、追求卓越的叮咚人。<br />

                                    在这里，人人参与到公司运营，畅通表达。最终由一人担当主责，拍板决策。
                                
                                </p>
                            </div>
                        </div>
                    </div>-->
                <?php echo htmlspecialchars_decode($object['value']);?>
            </div>
        </div>

        <div id="footer">
            <p class="link">
                <a href="<?php echo U('index/download');?>" target="_blank">APP下载</a> <span>|</span>
                <a href="<?php echo U('index/media');?>">媒体资料</a> <span>|</span>
                <a href="<?php echo U('index/contactus');?>">联系我们</a>
            </p>
            <p class="copyright">
                &copy;<?php echo htmlspecialchars_decode($config['mallFooter']);?>
            </p>
            <!--<p class="license">
              <a target="_blank" href="http://beian.miit.gov.cn" style="display:inline-block;text-decoration:none;">
                <img src="/Apps/Home/View/pc/images/beian.png" style="float:left;" />
                <span style="float:left; padding-left: 10px;"> 苏ICP备19021499号-1</span>
              </a>
            </p>-->
        </div>

    </div>

    <script>
        // 导航栏点击事件
        $(".ipone_nav_icon").click(function () {
            $(".ipone_navList").stop().slideToggle("slow");
            $(".ipone_navList_info").stop().slideToggle("slow");

        });


        // 点击遮罩层关闭右侧边栏
        $('.mark').click(function () {
            $(".ipone_navList").slideUp();
            $(".ipone_navList_info").slideUp()
        })

    </script>
</body>

</html>