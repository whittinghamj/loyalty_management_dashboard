<?php

// include main functions
include( dirname(__FILE__).'/includes/core.php' );
include( dirname(__FILE__).'/includes/functions.php' );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo $globals['platform_name']; ?></title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="Advanced next generation content delivery, performance and security platform." name="description" />
    <meta content="CloudShield.io" name="author" />

    <link rel="apple-touch-icon" sizes="57x57" href="assets/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="assets/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="assets/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="96x96" href="assets/favicon/favicon-96x96.png">
    <link rel="manifest" href="assets/favicon/manifest.json">
    <meta name="msapplication-TileImage" content="assets/favicon/ms-icon-144x144.png">
    
    <!-- core css -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link href="assets/css/default/app.min.css" rel="stylesheet" />
    <link href="assets/css/default/theme/blue.min.css" rel="stylesheet" />

</head>
<body class="pace-top">
    <div id="page-loader" class="fade show">
        <span class="spinner"></span>
    </div>
    
    <div class="login-cover">
        <div class="login-cover-image" style="background-image: url(assets/img/background_1.jpg)" data-id="login-cover-image"></div>
        <div class="login-cover-bg"></div>
    </div>
    
    <div id="page-container" class="fade">
        <div class="login login-v2" data-pageload-addclass="animated fadeIn">
            <div class="login-header">
                <div class="brand">
                    <img src="assets/img/logo_picture.png" width="100%" alt="Loyalty Dashboard">
                </div>
            </div>

            <div class="login-content">
                <form action="login.php" method="POST" class="margin-bottom-0">
                    <div class="form-group m-b-20">
                        <input type="text" name="email" class="form-control form-control-lg" placeholder="Email Address" required />
                    </div>
                    <div class="form-group m-b-20">
                        <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required />
                    </div>
                    <!--
                        <div class="checkbox checkbox-css m-b-20">
                            <input type="checkbox" id="remember_checkbox" /> 
                            <label for="remember_checkbox">
                                Remember Me
                            </label>
                        </div>
                    -->
                    <div class="login-buttons">
                        <button type="submit" class="btn btn-success btn-block btn-lg">Sign In</button>
                    </div>
                    <!--
                        <div class="m-t-20">
                            Not a member yet? Click <a href="javascript:;">here</a> to register.
                        </div>
                    -->
                </form>
            </div>
        </div>
    </div>
    
    <!-- ================== BEGIN BASE JS ================== -->
    <script src="assets/js/app.min.js"></script>
    <script src="assets/js/theme/default.min.js"></script>
    <!-- ================== END BASE JS ================== -->
    
    <!-- ================== BEGIN PAGE LEVEL JS ================== -->
    <script src="assets/js/demo/login-v2.demo.js"></script>
    <!-- ================== END PAGE LEVEL JS ================== -->
</body>
</html>