<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// session start
session_start();

// start timer for page loaded var
$time = microtime();
$time = explode( ' ', $time );
$time = $time[1] + $time[0];
$start = $time;

// vars
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);
$globals['dev']			 = true;
$globals['basedir']		 = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

// login check
if( !isset( $_SESSION['logged_in'] ) || $_SESSION['logged_in'] != true ) {
	// redirect to index.php
	go( 'index.php' );
} else {
	if( !isset( $_SESSION['account_details'] ) ) {
		$account_details = account_details( $_SESSION['account']['id'] );
		$_SESSION['account_details'] = $account_details;
	} else {
		$account_details = $_SESSION['account_details'];
	}

	// calculate allowed vs installed proxies
	// get installed proxies
	$account_details['installed_proxies'] 		= total_servers_account_wide();

	// count active allowed proxies
	$account_details['allowed_proxies'] = 0;
	foreach( $account_details['products'] as $product ) {
		if( $product['pid'] == '82' && $product['status'] == 'Active' ) {
			$account_details['allowed_proxies']++;
		}
	}

	if( $account_details['installed_proxies'] >= $account_details['allowed_proxies'] ) {
		$account_details['allow_more_proxies'] = false;
	} else {
		$account_details['allow_more_proxies'] = true;
	}

	// get local user data
	$account_details['local_data'] = account_details_local( $_SESSION['account']['id'] );
}

// geoip database
require( $globals['basedir'].'assets/geoip/MaxMind-DB-Reader-php/autoload.php' );
use MaxMind\Db\Reader;
$geoip = new Reader( $globals['basedir'].'assets/geoip/GeoLite2-City.mmdb' );
$geoisp = new Reader( $globals['basedir'].'assets/geoip/GeoIP2-ISP.mmdb' );

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

	<!-- datatables -->
	<link href="assets/plugins/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
	<link href="assets/plugins/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" />

	<!-- website tutorial -->
	<link href="assets/intro/introjs.css" rel="stylesheet">

	<!-- select2 -->
	<link href="assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" />

	<!-- apple switch -->
	<link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet" />

	<!-- cloudshield custom css -->
	<link href="assets/css/cloudshield.css" rel="stylesheet" />
</head>

<body class="boxed-layout">
	<div id="page-loader" class="fade show">
		<span class="spinner"></span>
	</div>
	
	<div id="page-container" class="page-container fade page-sidebar-fixed page-header-fixed">
		<div id="header" class="header navbar-inverse">
			<div class="navbar-header">
				<a href="dashboard.php" class="navbar-brand"><img src="assets/img/logo_no_text.svg" height="100%" alt="CloudShield Logo"> &nbsp;&nbsp; <b><font color="#d94227">Cloud</font></b>Shield</a>
				<button type="button" class="navbar-toggle" data-click="sidebar-toggled">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
			</div>
			<ul class="navbar-nav navbar-right">
				<!--
					<li class="navbar-form">
						<form action="" method="POST" name="search">
							<div class="form-group">
								<input type="text" class="form-control" placeholder="Enter keyword" />
								<button type="submit" class="btn btn-search"><i class="fa fa-search"></i></button>
							</div>
						</form>
					</li>
				-->
				<!--
					<li class="dropdown">
						<a href="#" data-toggle="dropdown" class="dropdown-toggle f-s-14">
							<i class="fa fa-bell"></i>
							<span class="label">5</span>
						</a>
						<div class="dropdown-menu media-list dropdown-menu-right">
							<div class="dropdown-header">NOTIFICATIONS (5)</div>
							<a href="javascript:;" class="dropdown-item media">
								<div class="media-left">
									<i class="fa fa-bug media-object bg-silver-darker"></i>
								</div>
								<div class="media-body">
									<h6 class="media-heading">Server Error Reports <i class="fa fa-exclamation-circle text-danger"></i></h6>
									<div class="text-muted f-s-10">3 minutes ago</div>
								</div>
							</a>
							<a href="javascript:;" class="dropdown-item media">
								<div class="media-left">
									<img src="assets/img/user/user-1.jpg" class="media-object" alt="" />
									<i class="fab fa-facebook-messenger text-blue media-object-icon"></i>
								</div>
								<div class="media-body">
									<h6 class="media-heading">John Smith</h6>
									<p>Quisque pulvinar tellus sit amet sem scelerisque tincidunt.</p>
									<div class="text-muted f-s-10">25 minutes ago</div>
								</div>
							</a>
							<a href="javascript:;" class="dropdown-item media">
								<div class="media-left">
									<img src="assets/img/user/user-2.jpg" class="media-object" alt="" />
									<i class="fab fa-facebook-messenger text-blue media-object-icon"></i>
								</div>
								<div class="media-body">
									<h6 class="media-heading">Olivia</h6>
									<p>Quisque pulvinar tellus sit amet sem scelerisque tincidunt.</p>
									<div class="text-muted f-s-10">35 minutes ago</div>
								</div>
							</a>
							<a href="javascript:;" class="dropdown-item media">
								<div class="media-left">
									<i class="fa fa-plus media-object bg-silver-darker"></i>
								</div>
								<div class="media-body">
									<h6 class="media-heading"> New User Registered</h6>
									<div class="text-muted f-s-10">1 hour ago</div>
								</div>
							</a>
							<a href="javascript:;" class="dropdown-item media">
								<div class="media-left">
									<i class="fa fa-envelope media-object bg-silver-darker"></i>
									<i class="fab fa-google text-warning media-object-icon f-s-14"></i>
								</div>
								<div class="media-body">
									<h6 class="media-heading"> New Email From John</h6>
									<div class="text-muted f-s-10">2 hour ago</div>
								</div>
							</a>
							<div class="dropdown-footer text-center">
								<a href="javascript:;">View more</a>
							</div>
						</div>
					</li>
				-->
				<li class="dropdown navbar-user">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						<!-- <img src="assets/img/user/user-13.jpg" alt="avatar" /> --> 
						<span class="d-none d-md-inline" style="color: white;"><?php echo $account_details['firstname'].' '.$account_details['lastname']; ?></span> <b class="caret"></b>
					</a>
					<div class="dropdown-menu dropdown-menu-right">
						<a href="javascript:;" class="dropdown-item">Edit Profile</a>
						<a href="actions.php?a=whmcs_support" class="dropdown-item" target="_blank">Support Tickets</a>
						<div class="dropdown-divider"></div>
						<a href="logout.php" class="dropdown-item">Sign Out</a>
					</div>
				</li>
			</ul>
		</div>
		
		<div id="sidebar" class="sidebar">
			<div data-scrollbar="true" data-height="100%">
				<!--
					<ul class="nav">
						<li class="nav-profile">
							<a href="javascript:;" data-toggle="nav-profile">
								<div class="cover with-shadow"></div>
								<div class="image">
									<img src="assets/img/user/user-13.jpg" alt="" />
								</div>
								<div class="info">
									<b class="caret pull-right"></b>Sean Ngu
									<small>Front end developer</small>
								</div>
							</a>
						</li>
						<li>
							<ul class="nav nav-profile">
								<li><a href="javascript:;"><i class="fa fa-cog"></i> Settings</a></li>
								<li><a href="javascript:;"><i class="fa fa-pencil-alt"></i> Send Feedback</a></li>
								<li><a href="javascript:;"><i class="fa fa-question-circle"></i> Helps</a></li>
							</ul>
						</li>
					</ul>
				-->
				<ul class="nav"><li class="nav-header">Navigation</li>
					<li <?php if( get( 'c' ) == '' || get( 'c' ) == 'home' ) { echo'class="active"'; } ?>>
						<a href="dashboard.php">
							<i class="fa fa-th-large"></i>
							<span>Dashboard</span> 
						</a>
					</li>
					<li <?php if( get( 'c' ) == 'cluster' || get( 'c' ) == 'clusters' || get( 'c' ) == 'cluster_edit' ) { echo'class="active"'; } ?>>
						<a href="dashboard.php?c=clusters">
							<i class="fa fa-cloud"></i>
							<span>Clusters</span> 
						</a>
					</li>
					<li <?php if( get( 'c' ) == 'domain_names' || get( 'c' ) == 'domain_name' ) { echo'class="active"'; } ?>>
						<a href="dashboard.php?c=domain_names">
							<i class="fa fa-globe"></i>
							<span>Domain Names</span> 
						</a>
					</li>
					<?php if( $_SESSION['account']['id'] == 7 ) { ?>
						<li <?php if( get( 'c' ) == 'smartdns' ) { echo'class="active"'; } ?>>
							<a href="dashboard.php?c=smartdns">
								<i class="fa fa-user-secret"></i>
								<span>SmartDNS</span> 
							</a>
						</li>
						<li <?php if( get( 'c' ) == 'staging' ) { echo'class="active"'; } ?>>
							<a href="dashboard.php?c=staging">
								<i class="fa fa-wrench"></i>
								<span>Staging</span> 
							</a>
						</li>
					<?php } ?>
					<li>
						<a href="logout.php">
							<i class="fa fa-sign-out-alt"></i>
							<span>Sign Out</span> 
						</a>
					</li>

					<!--
						<li class="has-sub">
							<a href="javascript:;">
								<b class="caret"></b>
								<i class="fa fa-th-large"></i>
								<span>Dashboard</span>
							</a>
							<ul class="sub-menu">
								<li><a href="index.html">Dashboard v1</a></li>
								<li><a href="index_v2.html">Dashboard v2</a></li>
								<li><a href="index_v3.html">Dashboard v3</a></li>
							</ul>
						</li>
					-->
				</ul>
			</div>
		</div>

		<?php
			$c = get( 'c' );
			switch( $c ) {
				// dev
                case "staging":
                    staging();
                    break;

                // account_settings
                case "account_settings":
                    account_settings();
                    break;

                // cluster
				case "cluster":
					cluster();
					break;

				// cluster_add
				case "cluster_add":
					cluster_add();
					break;

				// cluster_edit
				case "cluster_edit":
					cluster_edit();
					break;

                // clusters
				case "clusters":
					clusters();
					break;

				// domain_name
                case "domain_name":
                    domain_name();
                    break;

                // domain_names
                case "domain_names":
                    domain_names();
                    break;

                // home
                case "home":
                    home();
                    break;

				// not_found
				case "not_found":
					not_found();
					break;

				// smartdns
                case "smartdns":
                    smartdns();
                    break;

				// server_edit
				case "server_edit":
					server_edit();
					break;

				// default
				default:
					home();
					break;
			}
		?>

		<?php function cluster() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $cluster			  	= get_cluster( get( 'id' ) ); ?>
			<?php $domain_names 		= get_domain_names(); ?>
			<?php $controllers		  	= get_servers( get( 'id' ) , 'controller' ); ?>
			<?php $proxies			  	= get_servers( get( 'id' ) , 'proxy' ); ?>
			<?php $totals['download']   = 0; ?>
			<?php $totals['upload']	 	= 0; ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item"><a href="dashboard.php?c=clusters">Clusters</a></li>
					<li class="breadcrumb-item active">Manage Cluster: <?php if( isset( $cluster['id'] ) ) { echo $cluster['name']; } ?></li>
				</ol>

				<h1 class="page-header">Manage Cluster: <?php if( isset( $cluster['id'] ) ) { echo $cluster['name']; } ?></h1>

				<?php if( !isset( $cluster['id'] ) ) { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel panel-danger">
								<div class="panel-heading">
									<h4 class="panel-title">Access Denied</h4>
								</div>
								<div class="panel-body">
									You do not have permission to access this asset. If you feel this is a mistake then please open a support ticket.
								</div>
							</div>
						</div>
					</div>
				<?php }else { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel">
								<div class="panel-body">
									<div class="row">
										<div class="col-xl-9 col-xs-12">
											<div id="status_message"></div>
										</div>
										<div class="col-xl-3 col-xs-12 text-right">
											<div class="btn-group">
												<a href="?c=cluster_edit&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-primary">Edit</a>
												<?php if( $cluster['state'] == 'live' ) { ?>
													<a href="actions.php?a=cluster_state&state=maintenance&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-green">Live Mode</a>
												<?php } elseif( $cluster['state'] == 'maintenance' ) { ?>
													<a href="actions.php?a=cluster_state&state=live&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-warning">Maintenance Mode</a>
												<?php } ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php if( empty( $cluster['iptv_main_server_ip_address'] ) || empty( $cluster['iptv_main_server_port'] ) ) { ?>
						<div class="row">
							<div class="col-xl-12">
								<div class="panel panel-warning">
									<div class="panel-heading">
										<h4 class="panel-title">Action Required</h4>
									</div>
									<div class="panel-body">
										Ooops, it looks like this Cluster is not fully configured. Please go to the <a href="?c=cluster_edit&id=<?php echo $cluster['id'];?>">Edit Cluster</a> section and provide the required details to complete the setup process.
									</div>
								</div>
							</div>
						</div>
					<?php } elseif( empty( $cluster['iptv_main_server_domain'] ) ) { ?>
						<div class="row">
							<div class="col-xl-12">
								<div class="panel panel-warning">
									<div class="panel-heading">
										<h4 class="panel-title">Action Required</h4>
									</div>
									<div class="panel-body">
										You need to assign a Domain Name to this Cluster. You can do this in the <a href="?c=cluster_edit&id=<?php echo $cluster['id'];?>">Edit Cluster</a> section.
									</div>
								</div>
							</div>
						</div>
					<?php } else { ?>
						<?php if( $cluster['state'] == 'maintenance' ) { ?>
							<div class="row">
								<div class="col-xl-12">
									<div class="alert alert-warning fade show m-b-0">Cluster is currently in Maintenance Mode. No traffic is being processed at this time.</div> <hr>
								</div>
							</div>
						<?php } ?>

						<?php if( isset( $controllers[0] ) ) { ?>
							<div class="row">
								<div class="col-xl-6 col-md-6 tutorial_total_clusters">
									<div class="widget widget-stats bg-blue">
										<div class="stats-icon"><i class="fa fa-server"></i></div>
										<div class="stats-info">
											<h4>Proxy Servers</h4>
											<p><?php echo total_servers( $cluster['id'] , 'proxy' ); ?></p>	
										</div>
										<div class="stats-link">
											<a href="javascript:;">Installed Proxies.</a>
										</div>
									</div>
								</div>
								<div class="col-xl-6 col-md-6 tutorial_total_controllers">
									<div class="widget widget-stats bg-info">
										<div class="stats-icon"><i class="fa fa-random"></i></div>
										<div class="stats-info">
											<h4>Bandwidth Down / Up</h4>
											<p><span id="stats_bandwidth">0 / 0</span></p>	
										</div>
										<div class="stats-link">
											<a href="javascript:;">Value are in MBit.</a>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>

						<div class="row">
							<div class="col-xl-12">
								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">Controllers</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<button class="btn btn-xs btn-green tutorial_controllers" data-toggle="modal" data-target="#controller_add_modal">Add Controller</button>
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_controllers();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<?php if( !isset( $controllers[0] ) ) { ?>
											<center>
												<h2>You need to add a Controller.</h2>
											</center>
										<?php } else { ?>
											<table id="table_controllers" class="table table-striped table-bordered table-td-valign-middle">
												<thead>
													<tr>
														<th class="text-nowrap" data-orderable="false"><strong>Controller</strong></th>
														<th class="text-nowrap" data-orderable="false"><strong>Stats</strong></th>
		                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>Server</strong></th>
														<th class="text-nowrap" data-orderable="false" width="1px"><strong>Software</strong></th>
														<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
													</tr>
												</thead>
												<tbody>
													<?php
														// defaults for stats
														$proxies_online = 0;
														$proxies_offline = 0;
														$proxies_total = 0;

														// build table
														foreach( $controllers as $controller ) {
															// geo ip
															$geo_ip = $geoip->get( $controller['ip_address'] );
															$geo_ip = json_decode( json_encode( $geo_ip ), true );

															$geo_isp = $geoisp->get( $controller['ip_address'] );
															$geo_isp = json_decode( json_encode( $geo_isp ), true );

															 // prep stats
															$controller['stats'] = json_decode( $controller['stats'], true );
															$controller['stats']['uptime'] = ( empty( $controller['stats']['uptime'] ) ? 'N/A' : uptime( $controller['stats']['uptime'] ) );
															$controller['stats']['cpu_usage'] = ( empty( $controller['stats']['cpu_usage'] ) ? 'N/A' : $controller['stats']['cpu_usage'] );
															$controller['stats']['ram_usage'] = ( empty( $controller['stats']['ram_usage'] ) ? 'N/A' : $controller['stats']['ram_usage'] );
															$controller['stats']['bandwidth_download'] = ( empty( $controller['stats']['bandwidth_download'] ) ? 'N/A' : number_format( $controller['stats']['bandwidth_download'] / 125, 0 ) );
															$controller['stats']['bandwidth_upload'] = ( empty( $controller['stats']['bandwidth_upload'] ) ? 'N/A' : number_format( $controller['stats']['bandwidth_upload'] / 125, 0 ) );
															$controller['stats']['nginx_active_connections'] = ( empty( $controller['stats']['nginx_active_connections'] ) ? '0' : $controller['stats']['nginx_active_connections'] );

															// build table row
															if( $controller['status'] == 'pending' ) {
																$table_row = '
																	<td>
																		<button class="btn btn-xs btn-info">Pending Install</button>
																	</td>
																';
															} elseif( $controller['status'] == 'installing' ) {
																$table_row = '
																	<td>
																		<button class="btn btn-xs btn-success">Installing</button>
																	</td>
																';
															} elseif( $controller['status'] == 'failed' ) {
																$table_row = '
																	<td>
																		<button class="btn btn-xs btn-danger">Install Failed</button>
																	</td>
																';
															} elseif( $controller['status'] == 'rebooting' || $controller['status'] == 'rebooted' ) {
																$table_row = '
																	<td>
																		<button class="btn btn-xs btn-info">Rebooting</button>
																	</td>
																';
															}

		                                                    // setup last_seen
		                                                    if( $controller['updated'] != 0 ) {
		                                                        $controller['stats']['last_seen'] = ( time() - $controller['updated'] );
		                                                        $controller['stats']['last_seen'] = uptime( $controller['stats']['last_seen'] );
		                                                    } else {
		                                                        $controller['stats']['last_seen'] = 'N/A';
		                                                    }

															// output
															echo '
																<tr>
																	<td>
																		<strong>IP:</strong> '.$controller['ip_address'].' | <strong>Hostname:</strong> '.$controller['hostname'].'<br>
																			'.( $controller['status_server'] == 'online' ? 
		                                                                        '<strong>Uptime:</strong> '.$controller['stats']['uptime'].' | ' : 
		                                                                        '<strong>Last Seen:</strong> '.$controller['stats']['last_seen']
		                                                                    ).'
		                                                                    <strong>ISP:</strong> '.$geo_isp['isp'].' &nbsp; <img src="assets/img/flags/'.$geo_ip['country']['iso_code'].'.png" height="15px" alt="">
																	</td>
																	'.( $controller['status'] != 'installed' ? 
																		$table_row.'
																				<td>
																				</td>
																				<td>
																				</td>
																		' : 
																		'<td>
																			'.( $controller['status_server'] == 'online' ? 
		                                                                        '<strong>CPU:</strong> '.$controller['stats']['cpu_usage'].'% | <strong>RAM:</strong> '.$controller['stats']['ram_usage'].'% <br>
																				<strong>BW In:</strong> '.$controller['stats']['bandwidth_download'].' Mbit | <strong>BW Out:</strong> '.$controller['stats']['bandwidth_upload'].' Mbit' : 
		                                                                        ''
		                                                                    ).'
																		</td>
																		<td>
																			'.( $controller['status_server'] == 'online' ? '<button class="btn btn-xs btn-success">Online</button>' : '<button class="btn btn-xs btn-danger">Offline</button>' ).'
																		</td>
																		<td>
																			'.( $controller['status_proxy'] == 'running' ? '<button class="btn btn-xs btn-success">Online</button>' : '<button class="btn btn-xs btn-danger">Offline</button>' ).'
																		</td>
																		' 
																	).'
																	<td>
																		<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">Actions<b class="caret"></b></button>
																		<div class="dropdown-menu dropdown-menu-right" role="menu">
																			<a href="?c=server_edit&id='.$controller['id'].'" class="dropdown-item">Edit</a>
																			<a href="#rebuild_modal_'.$controller['id'].'" class="dropdown-item" data-toggle="modal" data-target="#rebuild_modal_'.$controller['id'].'">Rebuild</a>
																			<a href="#reboot_modal_'.$controller['id'].'" class="dropdown-item" data-toggle="modal" data-target="#reboot_modal_'.$controller['id'].'">Reboot</a>
																			<a href="actions.php?a=controller_delete&id='.$controller['id'].'&cluster_id='.$cluster['id'].'" class="dropdown-item" onclick="return confirm(\'Are you sure?\')">Delete</a>
																		</div>
																	</td>
																</tr>
															';

															echo '
																<div class="modal fade" id="rebuild_modal_'.$controller['id'].'" tabindex="-1" role="dialog" aria-labelledby="rebuild_modal_'.$controller['id'].'" aria-hidden="true">
																   	<div class="modal-dialog modal-notice">
																      	<div class="modal-content">
																         	<div class="modal-header">
																            	<h5 class="modal-title" id="myModalLabel">Rebuild Controller</h5>
																            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
																            		x
																            	</button>
																         	</div>
																         	<div class="modal-body">
															               		<div class="row">
															                  		<div class="col-xl-12">
															                     		<strong>1. Rebuild &amp; Update</strong>
															                     		<p class="description">

																							Use this option to rebuild &amp; update this server and apply the latest configuration. Estimated time to rebuild is between 1-5 minutes.
															                     		</p>
															                  		</div>
															                  		<div class="col-xl-12">
															                     		<strong>2. Full Reinstall &amp; Update</strong>
															                     		<p class="description">
															                     			This option is only to be used if you have reinstalled your operating system and need to perform a full installation of CloudShield. Estimated time to install is between 10-15 minutes.
															                     		</p>
															                  		</div>
															               		</div>
																         	</div>
																         	<div class="modal-footer">
																	         	<div class="btn-group">
																					<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
																					<a href="actions.php?a=controller_rebuild&id='.$controller['id'].'&cluster_id='.$cluster['id'].'&type=rebuild" type="button" class="btn btn-xs btn-success">Rebuild &amp; Update</a>
																					<a href="actions.php?a=controller_rebuild&id='.$controller['id'].'&cluster_id='.$cluster['id'].'&type=install" type="button" class="btn btn-xs btn-green">Full Reinstall &amp; Update</a>
																				</div>
																			</div>
																      	</div>
																   	</div>
																</div>

																<div class="modal fade" id="reboot_modal_'.$controller['id'].'" tabindex="-1" role="dialog" aria-labelledby="reboot_modal_'.$controller['id'].'" aria-hidden="true">
																   	<div class="modal-dialog modal-notice">
																      	<div class="modal-content">
																         	<div class="modal-header">
																            	<h5 class="modal-title" id="myModalLabel">Reboot Controller</h5>
																            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
																            		x
																            	</button>
																         	</div>
																         	<div class="modal-body">
															               		<div class="row">
															                  		You are about to reboot this Controller. While the Controller is rebooting NO connections can be made between your customers and your origin site. This action can take between 3-10 minutes depending on the configuration of this server.
															               		</div>
																         	</div>
																         	<div class="modal-footer">
																         		<div class="btn-group">
																					<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
																					<a href="actions.php?a=server_reboot&id='.$controller['id'].'&cluster_id='.$cluster['id'].'" class="btn btn-xs btn-green" >Reboot</a>
																				</div>
																			</div>
																      	</div>
																   	</div>
																</div>
															';
														}
													?>
												</tbody>
											</table>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>

						<?php if( isset( $controllers[0] ) ) { ?>
							<div class="row">
								<div class="col-xl-12">
									<div class="panel panel-inverse">
										<div class="panel-heading">
											<h4 class="panel-title">Proxies</h4>
											<div class="panel-heading-btn">
												<div class="btn-group">
													<a href="actions.php?a=proxy_rebuild_all&cluster_id=<?php echo $cluster['id']; ?>" class="btn btn-xs btn-warning tutorial_proxy_rebuild_all" onclick="return confirm('This will take some time to complete. Are you sure?')">Rebuild All</a>
													<?php if( $account_details['allow_more_proxies'] == false ) { ?>
														<button class="btn btn-xs btn-green tutorial_proxy_add" data-toggle="modal" data-target="#allow_more_proxies_false">Add Proxy</button>
													<?php } else { ?>
														<button class="btn btn-xs btn-green tutorial_proxy_add" data-toggle="modal" data-target="#proxy_add_modal">Add Proxy</button>
													<?php } ?>
													<a class="btn btn-xs btn-info" href="javascript:void(0);" onclick="tutorial_proxies();">Tutorial &amp; Help</a>
												</div>
											</div>
										</div>
										<div class="panel-body">
											<?php if( !isset( $proxies[0] ) ) { ?>
												<center>
													<h2>You need to add a Proxy Server.</h2>
												</center>
											<?php } else { ?>
												<table id="table_proxies" class="table table-striped table-bordered table-td-valign-middle">
													<thead>
														<tr>
															<th class="text-nowrap" data-orderable="false"><strong>Proxy</strong></th>
															<th class="text-nowrap" data-orderable="false"><strong>Stats</strong></th>
			                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>Server</strong></th>
															<th class="text-nowrap" data-orderable="false" width="1px"><strong>Software</strong></th>
															<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
														</tr>
													</thead>
													<tbody>
														<?php
															// defaults for stats
															$proxies_online = 0;
															$proxies_offline = 0;
															$proxies_total = 0;

															// build table
															foreach( $proxies as $proxy ) {
																// geo ip
																$geo_ip = $geoip->get( $proxy['ip_address'] );
																$geo_ip = json_decode( json_encode( $geo_ip ), true );

																$geo_isp = $geoisp->get( $proxy['ip_address'] );
																$geo_isp = json_decode( json_encode( $geo_isp ), true );

																// prep stats
																$proxy['stats'] = json_decode( $proxy['stats'], true );
																$proxy['stats']['uptime'] = ( empty( $proxy['stats']['uptime'] ) ? 'N/A' : uptime( $proxy['stats']['uptime'] ) );
																$proxy['stats']['cpu_usage'] = ( empty( $proxy['stats']['cpu_usage'] ) ? 'N/A' : $proxy['stats']['cpu_usage'] );
																$proxy['stats']['ram_usage'] = ( empty( $proxy['stats']['ram_usage'] ) ? 'N/A' : $proxy['stats']['ram_usage'] );
																$proxy['stats']['bandwidth_download'] = ( empty( $proxy['stats']['bandwidth_download'] ) ? '0' : number_format( $proxy['stats']['bandwidth_download'] / 125, 0 ) );
																$proxy['stats']['bandwidth_upload'] = ( empty( $proxy['stats']['bandwidth_upload'] ) ? '0' : number_format( $proxy['stats']['bandwidth_upload'] / 125, 0 ) );
																$proxy['stats']['nginx_active_connections'] = ( empty( $proxy['stats']['nginx_active_connections'] ) ? '0' : $proxy['stats']['nginx_active_connections'] );

																// update bandwidth totals
																if( isset( $proxy['stats']['bandwidth_download'] ) && is_numeric( $proxy['stats']['bandwidth_download'] ) ) {
																	$totals['download'] = ( $totals['download'] + $proxy['stats']['bandwidth_download'] );
																}
																if( isset( $proxy['stats']['bandwidth_upload'] ) && is_numeric( $proxy['stats']['bandwidth_upload'] ) ) {
																	$totals['upload'] = ( $totals['upload'] + $proxy['stats']['bandwidth_upload'] );
																}

																// build table row
																if( $proxy['status'] == 'pending' ) {
																	$table_row = '
																		<td>
																			<button class="btn btn-xs btn-info">Pending Install</button>
																		</td>
																	';
																} elseif( $proxy['status'] == 'installing' ) {
																	$table_row = '
																		<td>
																			<button class="btn btn-xs btn-success">Installing</button>
																		</td>
																	';
																} elseif( $proxy['status'] == 'failed' ) {
																	$table_row = '
																		<td>
																			<button class="btn btn-xs btn-danger">Install Failed</button>
																		</td>
																	';
																} elseif( $proxy['status'] == 'rebooting' || $proxy['status'] == 'rebooted' ) {
																	$table_row = '
																		<td>
																			<button class="btn btn-xs btn-info">Rebooting</button>
																		</td>
																	';
																}

		                                                        // setup last_seen
		                                                        if( $proxy['updated'] != 0 ) {
		                                                            $proxy['stats']['last_seen'] = ( time() - $proxy['updated'] );
		                                                            $proxy['stats']['last_seen'] = uptime( $proxy['stats']['last_seen'] );
		                                                        } else {
		                                                            $proxy['stats']['last_seen'] = 'N/A';
		                                                        }

																// output
																echo '
																	<tr>
																		<td>
																			<strong>IP:</strong> '.$proxy['ip_address'].' | <strong>Hostname:</strong> '.$proxy['hostname'].'<br>
																			'.( $proxy['status_server'] == 'online' ? 
		                                                                        '<strong>Uptime:</strong> '.$proxy['stats']['uptime'].' | ' : 
		                                                                        '<strong>Last Seen:</strong> '.$proxy['stats']['last_seen']
		                                                                    ).'
		                                                                    <strong>ISP:</strong> '.$geo_isp['isp'].' &nbsp; <img src="assets/img/flags/'.$geo_ip['country']['iso_code'].'.png" height="15px" alt="">
																		</td>
																		'.( $proxy['status'] != 'installed' ? 
																			$table_row.'
																				<td>
																				</td>
																				<td>
																				</td>
																			' : 
																			'<td>
																				'.( $proxy['status_server'] == 'online' ? 
			                                                                        '<strong>CPU:</strong> '.$proxy['stats']['cpu_usage'].'% | <strong>RAM:</strong> '.$proxy['stats']['ram_usage'].'% <br>
																					<strong>BW In:</strong> '.$proxy['stats']['bandwidth_download'].' Mbit | <strong>BW Out:</strong> '.$proxy['stats']['bandwidth_upload'].' Mbit' : 
			                                                                        ''
			                                                                    ).'
																			</td>
																			<td>
																				'.( $proxy['status_server'] == 'online' ? '<button class="btn btn-xs btn-success">Online</button>' : '<button class="btn btn-xs btn-danger">Offline</button>' ).'
																			</td>
																			<td>
																				'.( $proxy['status_proxy'] == 'running' ? '<button class="btn btn-xs btn-success">Online</button>' : '<button class="btn btn-xs btn-danger">Offline</button>' ).'
																			</td>
																			' 
																		).'
																		<td>
																			<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">Actions<b class="caret"></b></button>
																			<div class="dropdown-menu dropdown-menu-right" role="menu">
																				<a href="?c=server_edit&id='.$proxy['id'].'" class="dropdown-item">Edit</a>
																				<a href="#rebuild_modal_'.$proxy['id'].'" class="dropdown-item" data-toggle="modal" data-target="#rebuild_modal_'.$proxy['id'].'">Rebuild</a>
																				<a href="#reboot_modal_'.$proxy['id'].'" class="dropdown-item" data-toggle="modal" data-target="#reboot_modal_'.$proxy['id'].'">Reboot</a>
																				<a href="actions.php?a=proxy_delete&id='.$proxy['id'].'&cluster_id='.$cluster['id'].'" class="dropdown-item" onclick="return confirm(\'Are you sure?\')">Delete</a>
																			</div>
																		</td>
																	</tr>
																';

																echo '
																	<div class="modal fade" id="rebuild_modal_'.$proxy['id'].'" tabindex="-1" role="dialog" aria-labelledby="rebuild_modal_'.$proxy['id'].'" aria-hidden="true">
																	   	<div class="modal-dialog modal-notice">
																	      	<div class="modal-content">
																	         	<div class="modal-header">
																	            	<h5 class="modal-title" id="myModalLabel">Rebuild Proxy</h5>
																	            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
																	            		x
																	            	</button>
																	         	</div>
																	         	<div class="modal-body">
																               		<div class="row">
																                  		<div class="col-xl-12">
																                     		<strong>1. Rebuild &amp; Update</strong>
																                     		<p class="description">

																								Use this option to rebuild &amp; update this server and apply the latest configuration. Estimated time to rebuild is between 1-5 minutes.
																                     		</p>
																                  		</div>
																                  		<div class="col-xl-12">
																                     		<strong>2. Full Reinstall &amp; Update</strong>
																                     		<p class="description">
																                     			This option is only to be used if you have reinstalled your operating system and need to perform a full installation of CloudShield. Estimated time to install is between 10-15 minutes.
																                     		</p>
																                  		</div>
																               		</div>
																	         	</div>
																	         	<div class="modal-footer">
																		         	<div class="btn-group">
																						<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
																						<a href="actions.php?a=proxy_rebuild&id='.$proxy['id'].'&cluster_id='.$cluster['id'].'&type=rebuild" type="button" class="btn btn-xs btn-success">Rebuild &amp; Update</a>
																						<a href="actions.php?a=proxy_rebuild&id='.$proxy['id'].'&cluster_id='.$cluster['id'].'&type=install" type="button" class="btn btn-xs btn-green">Full Reinstall &amp; Update</a>
																					</div>
																				</div>
																	      	</div>
																	   	</div>
																	</div>

																	<div class="modal fade" id="reboot_modal_'.$proxy['id'].'" tabindex="-1" role="dialog" aria-labelledby="reboot_modal_'.$proxy['id'].'" aria-hidden="true">
																	   	<div class="modal-dialog modal-notice">
																	      	<div class="modal-content">
																	         	<div class="modal-header">
																	            	<h5 class="modal-title" id="myModalLabel">Reboot Proxy</h5>
																	            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
																	            		x
																	            	</button>
																	         	</div>
																	         	<div class="modal-body">
																               		<div class="row">
																                  		You are about to reboot this Proxy. This action can take between 3-10 minutes depending on the configuration of this server.
																               		</div>
																	         	</div>
																	         	<div class="modal-footer">
																	         		<div class="btn-group">
																						<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
																						<a href="actions.php?a=server_reboot&id='.$proxy['id'].'&cluster_id='.$cluster['id'].'" class="btn btn-xs btn-green">Reboot</a>
																					</div>
																				</div>
																	      	</div>
																	   	</div>
																	</div>
																';
															}
														?>
													</tbody>
												</table>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>

						<script>
							document.getElementById('stats_bandwidth').innerHTML = '<?php echo number_format( $totals['download'] ); ?> / <?php echo number_format( $totals['upload'] ); ?>';
						</script>

						<div class="modal fade" id="allow_more_proxies_false" tabindex="-1" role="dialog" aria-labelledby="allow_more_proxies_false" aria-hidden="true">
						   	<div class="modal-dialog modal-notice">
						      	<div class="modal-content">
						         	<div class="modal-header">
						            	<h5 class="modal-title" id="myModalLabel">Add Proxy Server</h5>
						            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
						            		x
						            	</button>
						         	</div>
						         	<div class="modal-body">
					               		<div class="row">
					                  		<div class="col-md-12">
					                  			<strong>Installed Proxies:</strong> <?php echo $account_details['installed_proxies']; ?> <br>
					                  			<strong>Purchased Licences:</strong> <?php echo $account_details['allowed_proxies']; ?> <hr>
												You do not have enough available licences to perform this action. You can order additional licences <a href="https://clients.deltacolo.com/cart.php?a=add&pid=82" target="_blank"><strong>here</strong></a>.
					                  		</div>
					               		</div>
						         	</div>
						         	<div class="modal-footer">
										<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Close</button>
									</div>
						      	</div>
						   	</div>
						</div>
						
						<form class="form" method="post" action="actions.php?a=controller_add">
							<input type="hidden" name="cluster_id" value="<?php echo $cluster['id']; ?>">

							<div class="modal fade" id="controller_add_modal" tabindex="-1" role="dialog" aria-labelledby="controller_add_modal" aria-hidden="true">
							   	<div class="modal-dialog modal-notice">
							      	<div class="modal-content">
							         	<div class="modal-header">
							            	<h5 class="modal-title" id="myModalLabel">Add Controller</h5>
							            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
							            		x
							            	</button>
							         	</div>
							         	<div class="modal-body">
							         		<div class="row">
												<div class="col-md-12">
													<p>Please make sure the server meets the minimum system requirements. <br><br>
														<strong>CPU:</strong> Dual Core @ 2.0 Ghz or higher<br>
														<strong>RAM:</strong> 8 GB or higher<br>
														<strong>Connection:</strong> 10 Mbit or higher<br>
														<strong>IP Address:</strong> 1 IPv4 Address<br>
														<strong>OS:</strong> Ubuntu 18.04 Server Minimal
													</p>
												</div>
											</div>
						               		<div class="row">
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>IP Address</strong></label>
														<input type="text" name="ip_address" class="form-control" required/>
														<small>Example: 1.2.3.4</small>
													</div>
												</div>
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Port</strong></label>
														<input type="text" name="ssh_port" class="form-control" required/>
														<small>Example: 22</small>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Username</strong></label>
														<input type="text" name="ssh_username" class="form-control" required/>
														<small>This user <strong>MUST</strong> have full sudo permissions.</small>
													</div>
												</div>
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Password</strong></label>
														<input type="text" name="ssh_password" class="form-control" required/>
														<small>Enter the password for the SSH User.</small>
													</div>
												</div>
											</div>
							         	</div>
							         	<div class="modal-footer">
							         		<div class="btn-group">
												<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
												<button type="submit" class="btn btn-xs btn-green">Add Controller</button>
											</div>
										</div>
							      	</div>
							   	</div>
							</div>
						</form>

						<form class="form" method="post" action="actions.php?a=proxy_add">
							<input type="hidden" name="cluster_id" value="<?php echo $cluster['id']; ?>">

							<div class="modal fade" id="proxy_add_modal" tabindex="-1" role="dialog" aria-labelledby="proxy_add_modal" aria-hidden="true">
							   	<div class="modal-dialog modal-notice">
							      	<div class="modal-content">
							         	<div class="modal-header">
							            	<h5 class="modal-title" id="myModalLabel">Add Proxy</h5>
							            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
							            		x
							            	</button>
							         	</div>
							         	<div class="modal-body">
							         		<div class="row">
												<div class="col-md-12">
													<p>Please make sure the server meets the minimum system requirements. <br><br>
														<strong>CPU:</strong> Dual Core @ 2.0 Ghz or higher<br>
														<strong>RAM:</strong> 8 GB or higher<br>
														<strong>Connection:</strong> 10 Mbit or higher<br>
														<strong>IP Address:</strong> 1 IPv4 Address<br>
														<strong>OS:</strong> Ubuntu 18.04 Server Minimal
													</p>
												</div>
											</div>
						               		<div class="row">
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>IP Address</strong></label>
														<input type="text" name="ip_address" class="form-control" required/>
														<small>Example: 1.2.3.4</small>
													</div>
												</div>
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Port</strong></label>
														<input type="text" name="ssh_port" class="form-control" required/>
														<small>Example: 22</small>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Username</strong></label>
														<input type="text" name="ssh_username" class="form-control" required/>
														<small>This user <strong>MUST</strong> have full sudo permissions.</small>
													</div>
												</div>
												<div class="col-xl-6 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>SSH Password</strong></label>
														<input type="text" name="ssh_password" class="form-control" required/>
														<small>Enter the password for the SSH User.</small>
													</div>
												</div>
											</div>
							         	</div>
							         	<div class="modal-footer">
							         		<div class="btn-group">
												<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
												<button type="submit" class="btn btn-xs btn-green">Add Proxy</button>
											</div>
										</div>
							      	</div>
							   	</div>
							</div>
						</form>

						<form class="form" method="post" action="actions.php?a=vpn_wizard">
							<input type="hidden" name="cluster_id" value="<?php echo $cluster['id']; ?>">

							<div class="modal fade" id="vpn_wizard_modal" tabindex="-1" role="dialog" aria-labelledby="vpn_wizard_modal" aria-hidden="true">
							   	<div class="modal-dialog modal-notice">
							      	<div class="modal-content">
							         	<div class="modal-header">
							            	<h5 class="modal-title" id="myModalLabel">VPN Wizard</h5>
							            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
							            		x
							            	</button>
							         	</div>
							         	<div class="modal-body">
							         		<div class="row">
												<div class="col-md-12">
													<p>
														The VPN Wizard will allow you to setup a custom sub-domain that your VPN clients can connect to to access your cluster. This enabled full point-to-point encryption for your clients and your content.
													</p>
												</div>
											</div>
						               		<div class="row">
												<div class="col-xl-12 col-xs-12">
													<div class="form-group">
														<label class="bmd-label-floating"><strong>Sub-domain</strong></label>
														<input type="text" name="subdomain" class="form-control" required/>
														<small>Example: vpn-cluster</small>
													</div>
												</div>
											</div>
							         	</div>
							         	<div class="modal-footer">
							         		<div class="btn-group">
												<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
												<button type="submit" class="btn btn-xs btn-green">Save</button>
											</div>
										</div>
							      	</div>
							   	</div>
							</div>
						</form>
					<?php } ?>
				<?php } ?>
			</div>
		<?php } ?>

		<?php function clusters() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $clusters = get_clusters(); ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item active">Clusters</li>
				</ol>

				<h1 class="page-header">Clusters</h1>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel">
							<div class="panel-body">
								<div class="row">
									<div class="col-xl-12">
										<div id="status_message"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel panel-inverse">
							<div class="panel-heading">
								<h4 class="panel-title">Clusters</h4>
								<div class="panel-heading-btn">
									<div class="btn-group">
										<button class="btn btn-xs btn-secondary tutorial_example_cluster" data-toggle="modal" data-target="#cluster_example_modal">How Clusters Work</button>
		        						<button class="btn btn-xs btn-warning tutorial_cluster_wizard" data-toggle="modal" data-target="#cluster_wizard_modal">Cluster Wizard</button>
		        						<button class="btn btn-xs btn-green tutorial_add_cluster" data-toggle="modal" data-target="#cluster_add_modal">Add Cluster</button>
										<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial();">Tutorial &amp; Help</a>
									</div>
								</div>
							</div>
							<div class="panel-body">
								<?php if( !isset( $clusters[0]['id'] ) ) { ?>
									<center>
										<h2>You need to add a Cluster.</h2>
									</center>
								<?php } else { ?>
									<table id="table_clusters" class="table table-striped table-bordered table-td-valign-middle">
										<thead>
											<tr>
												<th class="text-nowrap"><strong>Name</strong></th>
												<th class="text-nowrap"><strong>Domain</strong></th>
												<th class="text-nowrap" width="1px"><strong>Origin IP</strong></th>
												<th class="text-nowrap" data-orderable="false" width="1px"><strong>Origin Port</strong></th>
												<th class="text-nowrap" width="1px"><strong>Proxies</strong></th>
												<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
											</tr>
										</thead>
										<tbody>
											<?php
												// build table
												foreach( $clusters as $cluster ) {

													// output
													echo '
														<tr>
															<td>
																'.$cluster['name'].' 
																'.( $cluster['state'] == 'maintenance' ? '<a href="actions.php?a=cluster_state&state=live&id='.$cluster['id'].'" type="button" class="btn btn-xs btn-warning"><i class="fa fa-tools"></i></a>' : '' ).'
															</td>
															<td>
																'.$cluster['iptv_main_server_domain'].'
															</td>
															<td>
																'.$cluster['iptv_main_server_ip_address'].'
															</td>
															<td>
																'.$cluster['iptv_main_server_port'].'
															</td>
															<td>
																'.total_servers( $cluster['id'] , 'proxy').'
															</td>
															<td>
																<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">Actions<b class="caret"></b></button>
																<div class="dropdown-menu dropdown-menu-right" role="menu">
																	<a href="?c=cluster&id='.$cluster['id'].'" class="dropdown-item">Manage</a>
																	<a href="?c=cluster_edit&id='.$cluster['id'].'" class="dropdown-item">Edit</a>
																	<a href="actions.php?a=cluster_delete&id='.$cluster['id'].'" class="dropdown-item" onclick="return confirm(\'This will delete all proxies contained in this cluster. Are you sure?\')">Delete</a>
																</div>
															</td>
														</tr>
													';
												}
											?>
										</tbody>
									</table>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade" id="cluster_example_modal" tabindex="-1" role="dialog" aria-labelledby="cluster_example_modal" aria-hidden="true">
			   	<div class="modal-dialog modal-notice modal-xl">
			      	<div class="modal-content">
			         	<div class="modal-header">
			            	<h5 class="modal-title" id="myModalLabel">How Clusters Work</h5>
			            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			            		x
			            	</button>
			         	</div>
			         	<div class="modal-body">
		               		<div class="row">
		               			<div class="col-md-6 col-sm-12">
		               				Welcome to CloudShield. <br>
		               				<br>
									CloudShield is a next generation content filtering, protection and performance platform, making it impossible for anyone, with malicious intent or otherwise, to make direct contact with your origin server(s), or to locate, identify or damage them in any way. <br>
									<br>
									Based on the principle of using proxy servers as a shield between your customers (or anyone else) and your origin server(s), and using the 'cluster' concept, you have one or more controller servers that manage your cluster of secure proxy servers. When anyone enters your website address the controller first checks that the connection is an authentic one and not a direct security risk, and then selects a suitable proxy server to direct the customer to. <br>
									<br>
									Once the customer connection has been passed to a suitable proxy server, it is the proxy server that will communicate with your origin server(s) directly and not your customer. This improves performance and security as each controller and proxy has multiple security checkpoints to mitigate any security risk. This includes SQL Injection, Sniffing, Port Scanning and many others. Once the proxy obtains the requested information from your origin server it is then relayed back to the customer. This happens for every connection request so it is important to make sure you have the correct number of available proxy servers in the cluster to support your customers and their traffic. <br>
									<br>
									This image on the right hand side is a very basic example of what happens when a connection is made to the cluster and how the customer never interacts with your origin servers directly. Your cluster must collectively have enough bandwidth to equally match that available over all your origin servers combined. So, if you have a combined bandwidth of 50Gbit over all your origin servers then you must have 50Gbit available over your entire cluster. We highly recommend that your cluster servers have 1Gbit connections and not higher connection rates. This enables the cluster to evenly spread your customers over the entire cluster. This presents you with the added benefit of increased redundancy and greater performance. <br>
									<br>
									If you have more questions, please submit a support ticket. 
		               			</div>
		               			<div class="col-md-6 col-sm-12">
		                  			<img src="assets/img/cluster_example.svg" width="100%" alt="Example Reverse Cluster">
		                  		</div>
		               		</div>
			         	</div>
			         	<div class="modal-footer">
			         		<div class="btn-group">
								<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
							</div>
						</div>
			      	</div>
			   	</div>
			</div>

			<div class="modal fade" id="cluster_wizard_modal" tabindex="-1" role="dialog" aria-labelledby="cluster_wizard_modal" aria-hidden="true">
			   	<div class="modal-dialog modal-notice modal-xl">
			      	<div class="modal-content">
			         	<div class="modal-header">
			            	<h5 class="modal-title" id="myModalLabel">Cluster Design Wizard</h5>
			            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			            		x
			            	</button>
			         	</div>
			         	<div class="modal-body">
		               		<div class="row">
		               			<div class="col-md-6 col-sm-12">
		               				This function is not available yet.
		               			</div>
		               		</div>
			         	</div>
			         	<div class="modal-footer">
							<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
						</div>
			      	</div>
			   	</div>
			</div>

			<form class="form" method="post" action="actions.php?a=cluster_add">
				<div class="modal fade" id="cluster_add_modal" tabindex="-1" role="dialog" aria-labelledby="cluster_add_modal" aria-hidden="true">
				   	<div class="modal-dialog modal-notice">
				      	<div class="modal-content">
				         	<div class="modal-header">
				            	<h5 class="modal-title" id="myModalLabel">Add Cluster</h5>
				            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				            		x
				            	</button>
				         	</div>
				         	<div class="modal-body">
			               		<div class="row">
									<div class="col-xl-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Name</strong></label>
											<input type="text" name="name" class="form-control" required/>
											<small>Example: Awesome Cluster</small>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-xl-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Cluster Type</strong></label>
											<select name="type" class="default-select2 form-control">
												<option value="byos" selected>Bring Your Own Servers</option>
												<option value="digitalocean" disabled>DigitalOcean Smart Cluster - Coming Soon</option>
											</select>
										</div>
									</div>
								</div>
				         	</div>
				         	<div class="modal-footer">
				         		<div class="btn-group">
									<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
									<button type="submit" class="btn btn-xs btn-green">Add Cluster</button>
								</div>
							</div>
				      	</div>
				   	</div>
				</div>
			</form>
		<?php } ?>

		<?php function cluster_edit() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $cluster = get_cluster( get( 'id' ) ); ?>
			<?php $domain_names = get_domain_names(); ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item"><a href="dashboard.php?c=clusters">Clusters</a></li>
					<li class="breadcrumb-item active">Edit Cluster: <?php if( isset( $cluster['id'] ) ) { echo $cluster['name']; } ?></li>
				</ol>

				<h1 class="page-header">Edit Cluster: <?php if( isset( $cluster['id'] ) ) { echo $cluster['name']; } ?></h1>

				<?php if( !isset( $cluster['id'] ) ) { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel panel-danger">
								<div class="panel-heading">
									<h4 class="panel-title">Access Denied</h4>
								</div>
								<div class="panel-body">
									You do not have permission to access this asset. If you feel this is a mistake then please open a support ticket.
								</div>
							</div>
						</div>
					</div>
				<?php }elseif( !isset( $domain_names[0]['id'] ) ) { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel panel-warning">
								<div class="panel-heading">
									<h4 class="panel-title">Action Required</h4>
								</div>
								<div class="panel-body">
									You need to add at least one <a href="?c=domain_names">Domain Name</a>. Please add and validate a Domain Name.
								</div>
							</div>
						</div>
					</div>
				<?php }else { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel">
								<div class="panel-body">
									<div class="row">
										<div class="col-xl-9">
											<div id="status_message"></div>
										</div>
										<div class="col-xl-3 text-right">
											<div class="btn-group">
												<a href="?c=cluster&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-primary">Manage</a>
												<?php if( $cluster['state'] == 'live' ) { ?>
													<a href="actions.php?a=cluster_state&state=maintenance&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-green">Live Mode</a>
												<?php } elseif( $cluster['state'] == 'maintenance' ) { ?>
													<a href="actions.php?a=cluster_state&state=live&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-warning">Maintenance Mode</a>
												<?php } ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php if( $cluster['state'] == 'maintenance' ) { ?>
						<div class="row">
							<div class="col-xl-12">
								<div class="alert alert-warning fade show m-b-0">Cluster is currently in Maintenance Mode. No traffic is being processed at this time.</div> <hr>
							</div>
						</div>
					<?php } ?>

					<form class="form" method="post" action="actions.php?a=cluster_edit">
						<input type="hidden" name="id" value="<?php echo $cluster['id']; ?>">
						<div class="row">
							<div class="col-xl-6 col-md-12 col-xs-12">
								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">General Settings</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_1();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<div class="row">
											<div class="col-md-12 col-sm-12 tutorial_settings_cluster_name">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Name</strong></label>
													<input type="text" name="name" class="form-control" value="<?php echo $cluster['name']; ?>" required/>
													<small>Example: Awesome Cluster</small>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12 col-sm-12 tutorial_settings_origin_domain">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Domain Name</strong></label>
													<select name="iptv_main_server_domain" class="default-select2 form-control">
														<option value="0" selected disabled>Select a Domain Name</option>
														<?php 
															if( is_array( $domain_names ) && isset( $domain_names[0]['id'] ) ) {
																foreach( $domain_names as $domain_name ) {
																	if( $domain_name['status'] == 'active' ) {
																		echo '<option value="'.$domain_name['id'].'" '.($domain_name['id']==$cluster['domain_name_id']?'selected':'').'>'.$domain_name['domain_name'].'</option>';
																	}
																}
															} else {
																echo '<option value="0">No Active Domains Found</option>';
															}
														?>
													</select>
												</div>
											</div>
											<div class="col-md-12 col-sm-12 tutorial_settings_origin_ip_address">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Origin IP</strong></label>
													<input type="text" name="iptv_main_server_ip_address" class="form-control" value="<?php echo $cluster['iptv_main_server_ip_address']; ?>" required/>
													<small>Example: 1.2.3.4</small>
												</div>
											</div>
											<div class="col-md-12 col-sm-12 tutorial_settings_origin_port">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Origin Port</strong></label>
													<input type="text" name="iptv_main_server_port" class="form-control" value="<?php echo $cluster['iptv_main_server_port']; ?>" required/>
													<small>Example: 8080</small>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12 col-sm-12 tutorial_settings_cluster_notes">
												<div class="form-group">
													<label><strong>Notes</strong></label>
													<div class="form-group">
														<textarea name="notes" class="form-control" rows="2"><?php echo $cluster['notes']; ?></textarea>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_1">Save Changes</button>
									</div>
								</div>

								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">Advanced Settings</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_2();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<div class="row">
											<div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_cache">
	                                            	<label class="bmd-label-floating"><strong>Content Caching</strong></label>
													<select class="form-control" name="enable_cache">
														<option value="yes" <?php if( $cluster['enable_cache'] == 'yes') { echo 'selected'; } ?> >Enable Cache</option>
														<option value="no" <?php if( $cluster['enable_cache'] == 'no') { echo 'selected'; } ?> >Disable Cache</option>
													</select> <br>
													<small>This will enable or disable the cache system.</small>
	                                            </div>
	                                        </div>
											<div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_stalker">
	                                            	<label class="bmd-label-floating"><strong>Streaming Media Portal</strong></label>
													<select class="form-control" name="enable_stalker">
														<option value="yes" <?php if( $cluster['enable_stalker'] == 'yes') { echo 'selected'; } ?> >Enable Ministra / Stalker Portal</option>
														<option value="no" <?php if( $cluster['enable_stalker'] == 'no') { echo 'selected'; } ?> >Disable Ministra / Stalker Portal</option>
													</select> <br>
													<small>This option will block the OTT Ministra streaming portal which is available on some streaming media servers.</small>
	                                            </div>
	                                        </div>
											<div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_ip_fraud_protection">
	                                            	<label class="bmd-label-floating"><strong>IP Fraud Protection</strong></label>
													<select class="form-control" name="enable_firewall_droplist">
														<option value="yes" <?php if( $cluster['enable_firewall_droplist'] == 'yes') { echo 'selected'; } ?> >Enable IP Fraud Protection</option>
														<option value="no" <?php if( $cluster['enable_firewall_droplist'] == 'no') { echo 'selected'; } ?> >Disable IP Fraud Protection</option>
													</select> <br>
													<small>This option will block over 1 BILLION stolen and fraudulent IP addresses.</small>
	                                            </div>
	                                        </div>
										</div>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_2">Save Changes</button>
									</div>
								</div>
							</div>

							<div class="col-xl-6 col-md-12 col-xs-12">
								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">SSL Settings</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_3();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<div class="row">
	                                        <div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_ssl">
	                                            	<label class="bmd-label-floating"><strong>Inbound SSL</strong></label>
													<select class="form-control" name="enable_ssl" data-style="select-with-transition" title="Choose" data-size="2">
														<option value="yes" <?php if( $cluster['enable_ssl'] == 'yes') { echo 'selected'; } ?> >Enable Inbound Encryption</option>
														<option value="no" <?php if( $cluster['enable_ssl'] == 'no') { echo 'selected'; } ?> >Disable Inbound Encryption</option>
													</select> <br>
													<small>Encrypts traffic between the browser and CloudShield.</small>
	                                            </div>
	                                        </div>
	                                        <div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_ssl_out">
	                                            	<label class="bmd-label-floating"><strong>Oubound SSL</strong></label>
													<select class="form-control" name="enable_ssl_out" data-style="select-with-transition" title="Choose" data-size="2">
														<option value="yes" <?php if( $cluster['enable_ssl_out'] == 'yes') { echo 'selected'; } ?> >Enable Outbound Encryption</option>
														<option value="no" <?php if( $cluster['enable_ssl_out'] == 'no') { echo 'selected'; } ?> >Disable Outbound Encryption</option>
													</select> <br>
													<small>Encrypts traffic between CloudShield and your origin server.</small>
	                                            </div>
	                                        </div>
										</div>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_3">Save Changes</button>
									</div>
								</div>

								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">eVPN Settings</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_5();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<div class="row">
	                                        <div class="col-xl-12 col-sm-12">
	                                            <div class="form-group tutorial_settings_evpn">
	                                            	<label class="bmd-label-floating"><strong>Enable eVPN Cluster</strong></label>
													<select class="form-control" name="enable_evpn" data-style="select-with-transition" title="Choose" data-size="2">
														<option value="yes" <?php if( $cluster['enable_evpn'] == 'yes') { echo 'selected'; } ?> >Enable</option>
														<option value="no" <?php if( $cluster['enable_evpn'] == 'no') { echo 'selected'; } ?> >Disable</option>
													</select> <br>
													<small>Embedded VPN Server that runs on your CloudShield Cluster.</small>
	                                            </div>
	                                        </div>
										</div>
										<?php if( $cluster['enable_evpn'] == 'yes' ) { ?>
											<div class="row">
												<div class="col-md-12 col-sm-12 tutorial_settings_cluster_notes">
													<div class="form-group">
														<label><strong>eVPN Server Address</strong></label>
														<input type="text" name="evpn_address" class="form-control" value="<?php echo md5( $cluster['id'] ).'.'.$cluster['iptv_main_server_domain']; ?>" onclick="select()" readonly/>
														<small>This is your eVPN Server Address. This is already set in the Configuration file below.</small>
													</div>
												</div>
											</div>
											<div class="row">
		                                        <div class="col-xl-12 col-sm-12">
		                                            Your eVPN Connection details can be found below. There are a wide range of applications that your customers may use but the Configuration file is the most important. The Configuration file below has been configured for your unique Cluster. <br>
		                                            <br>
		                                            <a href="install_files/build_evpn.php?cluster_id=<?php echo $cluster['id']; ?>">Download Configuration File</a> <br>
		                                            <a href="downloads/openvpn-connect-3.2.3.2325_signed.dmg">Download OpenVPN Connect for macOS</a> <br>
		                                            <a href="downloads/openvpn-connect-3.2.2.1455_signed_x86.msi">Download OpenVPN Connect for Windows 32bit</a> <br>
		                                            <a href="downloads/openvpn-connect-3.2.2.1455_signed.msi">Download OpenVPN Connect for Windows 64bit</a> <br>
		                                        </div>
											</div>
										<?php } ?>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_5">Save Changes</button>
									</div>
								</div>

								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">Firewall Rules</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a class="btn btn-xs btn-green tutorial_settings_add_asn" href="#block_new_asn" data-toggle="modal" data-target="#block_new_asn">Add New Block</a>
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_4();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<table id="table_blocked_networks" class="table table-striped table-bordered table-td-valign-middle tutorial_settings_blocked_asns">
											<thead>
												<tr>
	                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>AS Number</strong></th>
													<th class="text-nowrap" data-orderable="false"><strong>Network Name</strong></th>
													<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
												</tr>
											</thead>
											<tbody>
												<?php
													// build table
													foreach( $cluster['blocked_networks'] as $blocked_network ) {

														// output
														echo '
															<tr>
																<td>
																	'.$blocked_network['asn'].'
																</td>
																<td>
																	'.$blocked_network['network_name'].'
																</td>
																<td>
																	<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">Actions<b class="caret"></b></button>
																	<div class="dropdown-menu dropdown-menu-right" role="menu">
																		<a href="https://bgpview.io/asn/'.$blocked_network['asn'].'" class="dropdown-item" target="_blank">ASN Information</a>
																		<a href="actions.php?a=blocked_network_delete&id='.$blocked_network['id'].'&cluster_id='.$cluster['id'].'" class="dropdown-item" onclick="return confirm(\'Are you sure?\')">Delete</a>
																	</div>
																</td>
															</tr>
														';
													}
												?>
											</tbody>
										</table>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_4">Save Changes</button>
									</div>
								</div>
							</div>
						</div>
					</form>

					<form class="form" method="post" action="actions.php?a=blocked_network_add">
						<input type="hidden" name="id" value="<?php echo $cluster['id']; ?>">
						
						<div class="modal fade" id="block_new_asn" tabindex="-1" role="dialog" aria-labelledby="block_new_asn" aria-hidden="true">
						   	<div class="modal-dialog modal-notice">
						      	<div class="modal-content">
						         	<div class="modal-header">
						            	<h5 class="modal-title" id="myModalLabel">Block Network by ASN</h5>
						            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
						            		x
						            	</button>
						         	</div>
						         	<div class="modal-body">
				                  		<div class="row">
											<div class="col-md-12 col-sm-12">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>ASN</strong></label>
													<input type="text" name="asn" class="form-control" required/>
													<small>Example: AS1234</small>
												</div>
											</div>
										</div>
						         	</div>
						         	<div class="modal-footer">
						         		<div class="btn-group">
											<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
											<button type="submit" class="btn btn-xs btn-green">Add ASN</button>
										</div>
									</div>
						      	</div>
						   	</div>
						</div>
					</form>
				<?php } ?>
			</div>
		<?php } ?>

		<?php function domain_name() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $domain_name = get_domain_name( get( 'id' ) ); ?>
			<?php $dns_records = get_dns_records( $domain_name['powerdns_id'] ); ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item"><a href="dashboard.php?c=domain_names">Domain Names</a></li>
					<li class="breadcrumb-item active">Domain Name: <?php if( isset( $domain_name['id'] ) ) { echo $domain_name['domain_name']; } ?></li>
				</ol>

				<h1 class="page-header">Domain Name: <?php if( isset( $domain_name['id'] ) ) { echo $domain_name['domain_name']; } ?></h1>

				<?php if( !isset( $domain_name['id'] ) ) { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel panel-danger">
								<div class="panel-heading">
									<h4 class="panel-title">Access Denied</h4>
								</div>
								<div class="panel-body">
									You do not have permission to access this asset. If you feel this is a mistake then please open a support ticket.
								</div>
							</div>
						</div>
					</div>
				<?php }else { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel">
								<div class="panel-body">
									<div class="row">
										<div class="col-xl-12">
											<div id="status_message"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12 col-md-12 col-xs-12">
							<div class="panel panel-inverse tutorial_dns_records">
								<div class="panel-heading">
									<h4 class="panel-title">DNS Records</h4>
									<div class="panel-heading-btn">
										<div class="btn-group">
											<?php if( $domain_name['status'] == 'active' ) { ?>
												<button class="btn btn-xs btn-green tutorial_add_dns_record" data-toggle="modal" data-target="#dns_add_modal">Add Record</button>
												<a href="actions.php?a=domain_name_resync_dns_records&id=<?php echo $domain_name['id']; ?>" class="btn btn-xs btn-success tutorial_resync_dns_records">Resync DNS Records</a>
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial();">Tutorial &amp; Help</a>
											<?php } ?>
										</div>
									</div>
								</div>
								<div class="panel-body">
									<?php if( $domain_name['status'] == 'pending' ) { ?>
										<h4>Change your nameservers for <?php echo $domain_name['domain_name']; ?></h4>
										Pointing to CloudShield's nameservers is critical for activating your site successfully. Otherwise, CloudShield is unable to manage your DNS and optimize your site. 
										<hr>
										<h4>1. Log in to your registrar account <?php if( !empty( $domain_name['registrar'] ) ) { echo 'at "'.ucwords( $domain_name['registrar'] ).'"'; } ?></h4>
										Remove the existing nameserver records or select 'Custom Nameservers' if applicable. <br>
										<br>
										<h4>2. Replace with CloudShield's nameservers:</h4>
										<div class="row">
											<div class="col-xl-12">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Nameserver 1</strong></label>
													<input type="text" class="form-control" value="ns1.cloudshield.io" readonly/>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-xl-12">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>Nameserver 2</strong></label>
													<input type="text" class="form-control" value="ns2.cloudshield.io" readonly/>
												</div>
											</div>
										</div>
										<br>
										Check to make sure they’re correct, then Save your changes. <br>
										<br>
										Registrars typically process nameserver updates within 24 hours. Once this process completes, your domain will be pointing to CloudShield's nameservers, and then this page will display the DNS records for this domain. <br>
									<?php } else { ?>
										<table id="table_dns_records" class="table table-striped table-bordered table-td-valign-middle">
											<thead>
												<tr>
	                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>Type</strong></th>
	                                                <th class="text-nowrap" width="1px"><strong>Name</strong></th>
	                                                <th class="text-nowrap" data-orderable="false"><strong>Content</strong></th>
	                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>TTL</strong></th>
	                                                <th class="text-nowrap" data-orderable="false" width="1px"><strong>Proxy Statue</strong></th>
													<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
												</tr>
											</thead>
											<tbody>
												<?php
													// build table
													foreach( $dns_records as $dns_record ) {
														if( $dns_record['type'] != 'SOA' && $dns_record['server_type'] != 'vpn' ) {
															// output
															echo '
																<tr>
																	<td>
																		'.$dns_record['type'].'
																	</td>
																	<td>
																		'.$dns_record['name'].'
																	</td>
																	<td>
																		'.$dns_record['content'].'
																	</td>
																	<td>
																		'.$dns_record['ttl'].'
																	</td>
																	<td>
																		'.( $dns_record['proxied'] == 'no' ? '<img src="assets/img/logo_no_text.svg" style="filter: grayscale(100%); opacity: 0.4;" height="25px" alt="CloudShield Logo">' : '<img src="assets/img/logo_no_text.svg" height="25px" alt="CloudShield Logo">').'
																	</td>
																	<td>
																		<button type="button" class="btn btn-xs btn-primary dropdown-toggle tutorial_dns_actions" data-toggle="dropdown">Actions<b class="caret"></b></button>
																		<div class="dropdown-menu dropdown-menu-right" role="menu">
																			<!-- <a href="?c=domain_name_record_edit&domain_id='.$domain_name['id'].'&record_id='.$dns_record['id'].'" class="dropdown-item">Edit</a> -->
																			<a href="actions.php?a=domain_name_record_delete&domain_id='.$domain_name['id'].'&record_id='.$dns_record['id'].'" class="dropdown-item" onclick="return confirm(\'Are you sure?\')">Delete</a>
																		</div>
																	</td>
																</tr>
															';
														}
													}
												?>
											</tbody>
										</table>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>

			<form class="form" method="post" action="actions.php?a=domain_name_record_add">
				<input type="hidden" name="domain_id" value="<?php echo $domain_name['id']; ?>">

				<div class="modal fade" id="dns_add_modal" tabindex="-1" role="dialog" aria-labelledby="dns_add_modal" aria-hidden="true">
				   	<div class="modal-dialog modal-notice">
				      	<div class="modal-content">
				         	<div class="modal-header">
				            	<h5 class="modal-title" id="myModalLabel">Add DNS Record</h5>
				            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				            		x
				            	</button>
				         	</div>
				         	<div class="modal-body">
			               		<div class="row">
									<div class="col-xl-6 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Type</strong></label>
											<select name="type" class="default-select2 form-control">
												<option value="A">A</option>
											</select>
										</div>
									</div>
									<div class="col-xl-6 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Name</strong></label>
											<input type="text" name="name" class="form-control" required/>
											<small>Example: web-server-01</small>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-xl-6 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Content</strong></label>
											<input type="text" name="content" class="form-control" required/>
											<small>Example: 142.214.1.172</small>
										</div>
									</div>
									<div class="col-xl-6 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Proxy Status</strong></label>
											<select name="proxied" class="default-select2 form-control">
												<option value="yes">Yes - Cluster Protection</option>
												<option value="no">No - DNS Only</option>
											</select>
										</div>
									</div>
								</div>
				         	</div>
				         	<div class="modal-footer">
				         		<div class="btn-group">
									<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
									<button type="submit" class="btn btn-xs btn-green">Add DNS Record</button>
								</div>
							</div>
				      	</div>
				   	</div>
				</div>
			</form>
		<?php } ?>

		<?php function domain_names() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $domain_names = get_domain_names(); ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item active">Domain Names</li>
				</ol>

				<h1 class="page-header">Domain Names</h1>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel">
							<div class="panel-body">
								<div class="row">
									<div class="col-xl-12">
										<div id="status_message"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel panel-inverse">
							<div class="panel-heading">
								<h4 class="panel-title">Domain Names</h4>
								<div class="panel-heading-btn">
									<div class="btn-group">
		        						<button class="btn btn-xs btn-green tutorial_add_domain" data-toggle="modal" data-target="#domain_add_modal">Add Domain Name</button>
										<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial();">Tutorial &amp; Help</a>
									</div>
								</div>
							</div>
							<div class="panel-body">
								<?php if( !isset( $domain_names[0]['id'] ) ) { ?>
									<center>
										<h2>You need to add a Domain Name.</h2>
									</center>
								<?php } else { ?>
									<table id="table_domain_names" class="table table-striped table-bordered table-td-valign-middle">
										<thead>
											<tr>
												<th class="text-nowrap"><strong>Domain Name</strong></th>
												<th class="text-nowrap" width="150px"><strong>Registrar</strong></th>
												<th class="text-nowrap" width="1px"><strong>Expires</strong></th>
												<th class="text-nowrap" width="200px"><strong>Status</strong></th>
												<th class="text-nowrap" data-orderable="false" width="1px"><strong>Actions</strong></th>
											</tr>
										</thead>
										<tbody>
											<?php
												// build table
												foreach( $domain_names as $domain_name ) {
													// build domain status
													if( $domain_name['status'] == 'active' ) {
														$domain_name['status'] ='<button class="btn btn-xs btn-success">Active</button>';
													} elseif( $domain_name['status'] == 'pending' ) {
														$domain_name['status'] ='<button class="btn btn-xs btn-info">Pending Nameserver Change</button>';
													} elseif( $domain_name['status'] == 'suspended' ) {
														$domain_name['status'] ='<button class="btn btn-xs btn-warning">Suspended</button>';
													} elseif( $domain_name['status'] == 'blocked' ) {
														$domain_name['status'] ='<button class="btn btn-xs btn-danger">Blocked</button>';
													} elseif( $domain_name['status'] == 'moved' ) {
														$domain_name['status'] ='<button class="btn btn-xs btn-danger">Moved Away</button>';
													}

													// expire cleanup
													$domain_name['expires'] = explode( 'T', $domain_name['expires'] );
													$domain_name['expires'] = $domain_name['expires'][0];

													// output
													echo '
														<tr>
															<td>
																'.$domain_name['domain_name'].'
															</td>
															<td>
																'.$domain_name['registrar'].'
															</td>
															<td>
																'.$domain_name['expires'].'
															</td>
															<td>
																'.$domain_name['status'].'
															</td>
															<td>
																<button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">Actions<b class="caret"></b></button>
																<div class="dropdown-menu dropdown-menu-right" role="menu">
																	<a href="?c=domain_name&id='.$domain_name['id'].'" class="dropdown-item">Manage</a>
																	<a href="actions.php?a=domain_name_delete&id='.$domain_name['id'].'" class="dropdown-item" onclick="return confirm(\'Deleting this domain name will suspend any active Clusters and servers using it. Are you sure?\')">Delete</a>
																</div>
															</td>
														</tr>
													';
												}
											?>
										</tbody>
									</table>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<form class="form" method="post" action="actions.php?a=domain_name_add">
				<div class="modal fade" id="domain_add_modal" tabindex="-1" role="dialog" aria-labelledby="domain_add_modal" aria-hidden="true">
				   	<div class="modal-dialog modal-notice">
				      	<div class="modal-content">
				         	<div class="modal-header">
				            	<h5 class="modal-title" id="myModalLabel">Add Domain Name</h5>
				            	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				            		x
				            	</button>
				         	</div>
				         	<div class="modal-body">
			               		<div class="row">
									<div class="col-xl-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Domain Name</strong></label>
											<input type="text" name="domain_name" class="form-control" required/>
											<small>Example: example.com</small>
										</div>
									</div>
								</div>
				         	</div>
				         	<div class="modal-footer">
				         		<div class="btn-group">
									<button type="button" class="btn btn-xs btn-default" data-dismiss="modal">Cancel</button>
									<button type="submit" class="btn btn-xs btn-green">Add Domain Name</button>
								</div>
							</div>
				      	</div>
				   	</div>
				</div>
			</form>
		<?php } ?>

		<?php function home() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item active"><a href="dashboard.php">Dashboard</a></li>
				</ol>

				<h1 class="page-header">Dashboard</h1>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel">
							<div class="panel-body">
								<div class="row">
									<div class="col-xl-12">
										<div id="status_message"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-3 col-md-6">
						<div class="widget widget-stats bg-blue tutorial_total_clusters">
							<div class="stats-icon"><i class="fa fa-cloud"></i></div>
							<div class="stats-info">
								<h4>Clusters</h4>
								<p><?php echo total_clusters(); ?></p>	
							</div>
							<div class="stats-link">
								<a href="?c=clusters">View Clusters <i class="fa fa-arrow-alt-circle-right"></i></a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-md-6">
						<div class="widget widget-stats bg-info tutorial_total_controllers">
							<div class="stats-icon"><i class="fa fa-server"></i></div>
							<div class="stats-info">
								<h4>Controllers</h4>
								<p><?php echo total_controllers_account_wide(); ?></p>	
							</div>
							<div class="stats-link">
								<a href="javascript:;">Total Installed Controllers</a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-md-6">
						<div class="widget widget-stats bg-orange tutorial_total_proxies">
							<div class="stats-icon"><i class="fa fa-network-wired"></i></div>
							<div class="stats-info">
								<h4>Proxies / Licenses</h4>
								<p><?php echo total_servers_account_wide(); ?> / <?php echo $account_details['allowed_proxies']; ?></p>	
							</div>
							<div class="stats-link">
								<a href="javascript:;">Total Installed Proxies</a>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-md-6">
						<div class="widget widget-stats bg-red tutorial_total_domains">
							<div class="stats-icon"><i class="fa fa-globe"></i></div>
							<div class="stats-info">
								<h4>Domain Names</h4>
								<p><?php echo total_domain_names(); ?></p>	
							</div>
							<div class="stats-link">
								<a href="?c=domain_names">View Domain Names <i class="fa fa-arrow-alt-circle-right"></i></a>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-6">
						<div class="panel panel-inverse tutorial_news">
							<div class="panel-heading">
								<h4 class="panel-title">News &amp; Updates</h4>
								<div class="panel-heading-btn">
									<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial();">Tutorial &amp; Help</a>
								</div>
							</div>
							<div class="panel-body">
								Today we are proud to release CloudShield v2 with an all new interface and features.
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php function not_found() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item active">Asset Not Found</li>
				</ol>

				<h1 class="page-header">Asset Not Found</h1>

				<div class="row">
					<div class="col-xl-12">
						<div class="panel panel-warning">
							<div class="panel-heading">
								<h4 class="panel-title">Asset Not Found</h4>
							</div>
							<div class="panel-body">
								The asset you tried to view does not exist. If you feel this is a mistake then please open a support ticket.
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php function server_edit() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<?php $server = get_server( get( 'id' ) ); ?>
			<?php $cluster = get_cluster( $server['cluster_id'] ); ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item"><a href="dashboard.php?c=clusters">Clusters</a></li>
					<li class="breadcrumb-item"><a href="dashboard.php?c=cluster&id=<?php echo $cluster['id']; ?>">Edit Cluster: <?php echo $cluster['name']; ?></a></li>
					<li class="breadcrumb-item active">Edit Server: <?php if( isset( $server['id'] ) ) { echo $server['hostname'].' '.$server['ip_address']; } ?></li>
				</ol>

				<h1 class="page-header">Edit Server: <?php if( isset( $server['id'] ) ) { echo $server['hostname'].' '.$server['ip_address']; } ?></h1>

				<?php if( !isset( $server['id'] ) ) { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel panel-danger">
								<div class="panel-heading">
									<h4 class="panel-title">Access Denied</h4>
								</div>
								<div class="panel-body">
									You do not have permission to access this asset. If you feel this is a mistake then please open a support ticket.
								</div>
							</div>
						</div>
					</div>
				<?php }else { ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="panel">
								<div class="panel-body">
									<div class="row">
										<div class="col-xl-10">
											<div id="status_message"></div>
										</div>
										<div class="col-xl-2 text-right">
											<a href="?c=cluster&id=<?php echo $cluster['id'];?>" type="button" class="btn btn-xs btn-primary">Manage Cluster</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<form class="form" method="post" action="actions.php?a=server_edit">
						<input type="hidden" name="id" value="<?php echo $server['id']; ?>">

						<div class="row">
							<div class="col-xl-12 col-md-12 col-xs-12">
								<div class="panel panel-inverse">
									<div class="panel-heading">
										<h4 class="panel-title">Server Settings</h4>
										<div class="panel-heading-btn">
											<div class="btn-group">
												<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="tutorial_1();">Tutorial &amp; Help</a>
											</div>
										</div>
									</div>
									<div class="panel-body">
										<div class="row">
											<div class="col-xl-6 col-xs-12">
												<div class="form-group">
													<label class="bmd-label-floating"><strong>IP Address</strong></label>
													<input type="text" name="ip_address" class="form-control" value="<?php echo $server['ip_address']; ?>" readonly/>
													<small>Example: 1.2.3.4</small>
												</div>
											</div>
											<div class="col-xl-6 col-xs-12">
												<div class="form-group tutorial_ssh_port">
													<label class="bmd-label-floating"><strong>SSH Port</strong></label>
													<input type="text" name="ssh_port" class="form-control" value="<?php echo $server['ssh_port']; ?>" required/>
													<small>Example: 22</small>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-xl-6 col-xs-12">
												<div class="form-group tutorial_ssh_username">
													<label class="bmd-label-floating"><strong>SSH Username</strong></label>
													<input type="text" name="ssh_username" class="form-control" value="<?php echo $server['ssh_username']; ?>" required/>
													<small>This user <strong>MUST</strong> have full sudo permissions.</small>
												</div>
											</div>
											<div class="col-xl-6 col-xs-12">
												<div class="form-group tutorial_ssh_password">
													<label class="bmd-label-floating"><strong>SSH Password</strong></label>
													<input type="text" name="ssh_password" class="form-control" value="<?php echo $server['ssh_password']; ?>" required/>
													<small>Enter the password for the SSH User.</small>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-xl-12 col-xs-12">
												<div class="form-group tutorial_notes">
													<label class="bmd-label-floating"><strong>Notes</strong></label>
													<textarea name="notes" class="form-control"><?php echo $server['notes'];?></textarea>
												</div>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<a href="?c=clusters" type="button" class="btn btn-xs btn-primary">Back</a>
										<button type="submit" class="btn btn-xs btn-green pull-right tutorial_settings_save_1">Save Changes</button>
									</div>
								</div>
							</div>
						</div>
					</form>
				<?php } ?>
			</div>
		<?php } ?>

		<?php function smartdns() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item active">SmartDNS</li>
				</ol>

				<h1 class="page-header">SmartDNS</h1>

				<div class="row">
					<div class="col-xl-12 col-xs-12">
						<div class="panel panel-inverse">
							<div class="panel-heading">
								<h4 class="panel-title">SmartDNS Servers</h4>
							</div>
							<div class="panel-body">
								CloudShield operates multiple SmartDNS servers that are 100% free to use. These are not your average DNS servers though, these are SmartDNS servers with some awesome features. Your ISP tracks, stores and sells data on everything you do online. The websites you visit, duration online and what you search on Google. Never have to worry about this ever again.<br>
								<br>
								<em>"SmartDNS is a technology based on using your DNS (Domain Name Server) to unblock geo-restricted sites and services. It's somewhat similar to how VPN servers route your internet traffic through a remote server to hide your real IP address and physical location."</em> <br>
								<br>
								<ul>
  									<li>We <strong><u>NEVER</u></strong> collect, share or sell your browsing data</li>
  									<li>Ultra Fast, Latest Generation Hardware</li>
  									<li>Hosted in Tier 4 Datacenters </li>
  									<li>Multiple Redundent Uplink Connections</li>
  									<li>Geo Unlocking Facilities</li>
  									<li>Free For <strong><u>EVERYONE</u></strong></li>
  								</ul>

  								Currently we have the following SmartDNS servers available. <br>
  								<br>
  								<div class="row">
									<div class="col-xl-4 col-md-4 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>Canada</strong></label>
											<div class="input-group">
												<div class="input-group-prepend"><span class="input-group-text"><img src="assets/img/flags/CA.png" height="18px" alt="Canada flag"></span></div>
												<input type="text" class="form-control" value="134.122.37.24" readonly/>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-md-4 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>United Kingdom</strong></label>
											<div class="input-group">
												<div class="input-group-prepend"><span class="input-group-text"><img src="assets/img/flags/GB.png" height="18px" alt="United Kingdom flag"></span></div>
												<input type="text" class="form-control" value="159.242.105.79" readonly/>
											</div>
										</div>
									</div>
									<div class="col-xl-4 col-md-4 col-xs-12">
										<div class="form-group">
											<label class="bmd-label-floating"><strong>United States</strong></label>
											<div class="input-group">
												<div class="input-group-prepend"><span class="input-group-text"><img src="assets/img/flags/US.png" height="18px" alt="United States flag"></span></div>
												<input type="text" class="form-control" value="157.230.11.65" readonly/>
											</div>
										</div>
									</div>
								</div>
								<br>
								Once you have entered one of the above IP addresses into your devices DNS Server settings section then you will need to clear you cache for the update to take effect. Once this has been done then navigate the internet as you normally would. Now that you are using a SmartDNS server, geographically locked content should not be available to you. For example, trying to access BBC iPlayer from outside the UK will result in a content locked message. When using the UK SmartDNS server, this will no longer be a problem and the locked content will now be available to you. <br>
								<br>
								Feel free to use our SmartDNS servers when ever and wherever you like, on your computer, your cell phone or even your servers. Help us spread the word and get your friends and family involved as well. Trust us, they will thank you for it.
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
		
		<?php function template() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>
			
			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
					<li class="breadcrumb-item"><a href="javascript:;">Page Options</a></li>
					<li class="breadcrumb-item active">Blank Page</li>
				</ol>
				<h1 class="page-header">Blank Page <small>header small text goes here...</small></h1>
				<div class="panel panel-inverse">
					<div class="panel-heading">
						<h4 class="panel-title">Panel Title here</h4>
						<div class="panel-heading-btn">
							
						</div>
					</div>
					<div class="panel-body">
						Panel Content Here
					</div>
				</div>
			</div>
		<?php } ?>

		<?php function staging() { ?>
			<?php global $conn, $globals, $account_details, $geoip, $geoisp; ?>

			<div id="content" class="content">
				<ol class="breadcrumb float-xl-right">
					<li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
					<li class="breadcrumb-item active">Staging</li>
				</ol>
				
				<h1 class="page-header">Staging <small>dev area for staff only</small></h1>
				
				<div class="panel panel-inverse">
					<div class="panel-heading">
						<h4 class="panel-title">Account Details</h4>
						<div class="panel-heading-btn">

						</div>
					</div>
					<div class="panel-body">
						<?php debug( $account_details ); ?>
					</div>
				</div>

				<div class="panel panel-inverse">
					<div class="panel-heading">
						<h4 class="panel-title">Globals</h4>
						<div class="panel-heading-btn">

						</div>
					</div>
					<div class="panel-body">
						<?php debug( $globals ); ?>
					</div>
				</div>
			</div>
		<?php } ?>

		<div id="footer" class="footer">
			<?php echo $globals['copyright']; ?>
		</div>
		
		<a href="javascript:;" class="btn btn-icon btn-circle btn-success btn-scroll-to-top fade" data-click="scroll-top"><i class="fa fa-angle-up"></i></a>
	
		<div class="modal fade" id="modal-terms" tabindex="-1" role="dialog" aria-labelledby="modal-terms" aria-hidden="true">
		   	<div class="modal-dialog modal-xl">
		      	<div class="modal-content">
		         	<div class="modal-header">
		            	<h5 class="modal-title" id="myModalLabel">Terms &amp; Conditions <small>(scroll to accept)</small></h5>
		         	</div>
		         	<div class="modal-body">
						<h2>Welcome to CloudShield</h2>
						<p>These terms and conditions outline the rules and regulations for the use of CloudShield's Website.</p> <br /> 

						<p>By accessing this website we assume you accept these terms and conditions in full. Do not continue to use CloudShield's website 
						if you do not accept all of the terms and conditions stated on this page.</p>
						<p>The following terminology applies to these Terms and Conditions, Privacy Statement and Disclaimer Notice
						and any or all Agreements: "Client", "You" and "Your" refers to you, the person accessing this website
						and accepting the Company's terms and conditions. "The Company", "Ourselves", "We", "Our" and "Us", refers
						to our Company. "Party", "Parties", or "Us", refers to both the Client and ourselves, or either the Client
						or ourselves. All terms refer to the offer, acceptance and consideration of payment necessary to undertake
						the process of our assistance to the Client in the most appropriate manner, whether by formal meetings
						of a fixed duration, or any other means, for the express purpose of meeting the Client's needs in respect
						of provision of the Company's stated services/products, in accordance with and subject to, prevailing law
						of . Any use of the above terminology or other words in the singular, plural,
						capitalisation and/or he/she or they, are taken as interchangeable and therefore as referring to same.</p><h2>Cookies</h2>
						<p>We employ the use of cookies. By using CloudShield's website you consent to the use of cookies 
						in accordance with CloudShield's privacy policy.</p><p>Most of the modern day interactive web sites
						use cookies to enable us to retrieve user details for each visit. Cookies are used in some areas of our site
						to enable the functionality of this area and ease of use for those people visiting. Some of our 
						affiliate / advertising partners may also use cookies.</p><h2>License</h2>
						<p>Unless otherwise stated, CloudShield and/or it's licensors own the intellectual property rights for
						all material on CloudShield. All intellectual property rights are reserved. You may view and/or print
						pages from https://www.cloudshield.io for your own personal use subject to restrictions set in these terms and conditions.</p>
						<p>You must not:</p>
						<ol>
						<li>Republish material from https://www.cloudshield.io</li>
						<li>Sell, rent or sub-license material from https://www.cloudshield.io</li>
						<li>Reproduce, duplicate or copy material from https://www.cloudshield.io</li>
						</ol>
						<p>Redistribute content from CloudShield (unless content is specifically made for redistribution).</p>
						<h2>Hyperlinking to our Content</h2>
						<ol>
						<li>The following organizations may link to our Web site without prior written approval:
						<ol>
						<li>Government agencies;</li>
						<li>Search engines;</li>
						<li>News organizations;</li>
						<li>Online directory distributors when they list us in the directory may link to our Web site in the same
						manner as they hyperlink to the Web sites of other listed businesses; and</li>
						<li>Systemwide Accredited Businesses except soliciting non-profit organizations, charity shopping malls,
						and charity fundraising groups which may not hyperlink to our Web site.</li>
						</ol>
						</li>
						</ol>
						<ol start="2">
						<li>These organizations may link to our home page, to publications or to other Web site information so long
						as the link: (a) is not in any way misleading; (b) does not falsely imply sponsorship, endorsement or
						approval of the linking party and its products or services; and (c) fits within the context of the linking
						party's site.
						</li>
						<li>We may consider and approve in our sole discretion other link requests from the following types of organizations:
						<ol>
						<li>commonly-known consumer and/or business information sources such as Chambers of Commerce, American
						Automobile Association, AARP and Consumers Union;</li>
						<li>dot.com community sites;</li>
						<li>associations or other groups representing charities, including charity giving sites,</li>
						<li>online directory distributors;</li>
						<li>internet portals;</li>
						<li>accounting, law and consulting firms whose primary clients are businesses; and</li>
						<li>educational institutions and trade associations.</li>
						</ol>
						</li>
						</ol>
						<p>We will approve link requests from these organizations if we determine that: (a) the link would not reflect
						unfavorably on us or our accredited businesses (for example, trade associations or other organizations
						representing inherently suspect types of business, such as work-at-home opportunities, shall not be allowed
						to link); (b)the organization does not have an unsatisfactory record with us; (c) the benefit to us from
						the visibility associated with the hyperlink outweighs the absence of CloudShield; and (d) where the
						link is in the context of general resource information or is otherwise consistent with editorial content
						in a newsletter or similar product furthering the mission of the organization.</p>

						<p>These organizations may link to our home page, to publications or to other Web site information so long as
						the link: (a) is not in any way misleading; (b) does not falsely imply sponsorship, endorsement or approval
						of the linking party and it products or services; and (c) fits within the context of the linking party's
						site.</p>

						<p>If you are among the organizations listed in paragraph 2 above and are interested in linking to our website,
						you must notify us by sending an e-mail to <a href="mailto:info@cloudshield.io" title="send an email to info@cloudshield.io">info@cloudshield.io</a>.
						Please include your name, your organization name, contact information (such as a phone number and/or e-mail
						address) as well as the URL of your site, a list of any URLs from which you intend to link to our Web site,
						and a list of the URL(s) on our site to which you would like to link. Allow 2-3 weeks for a response.</p>

						<p>Approved organizations may hyperlink to our Web site as follows:</p>

						<ol>
						<li>By use of our corporate name; or</li>
						<li>By use of the uniform resource locator (Web address) being linked to; or</li>
						<li>By use of any other description of our Web site or material being linked to that makes sense within the
						context and format of content on the linking party's site.</li>
						</ol>
						<p>No use of CloudShield's logo or other artwork will be allowed for linking absent a trademark license
						agreement.</p>
						<h2>Iframes</h2>
						<p>Without prior approval and express written permission, you may not create frames around our Web pages or
						use other techniques that alter in any way the visual presentation or appearance of our Web site.</p>
						<h2>Reservation of Rights</h2>
						<p>We reserve the right at any time and in its sole discretion to request that you remove all links or any particular
						link to our Web site. You agree to immediately remove all links to our Web site upon such request. We also
						reserve the right to amend these terms and conditions and its linking policy at any time. By continuing
						to link to our Web site, you agree to be bound to and abide by these linking terms and conditions.</p>
						<h2>Removal of links from our website</h2>
						<p>If you find any link on our Web site or any linked web site objectionable for any reason, you may contact
						us about this. We will consider requests to remove links but will have no obligation to do so or to respond
						directly to you.</p>
						<p>Whilst we endeavour to ensure that the information on this website is correct, we do not warrant its completeness
						or accuracy; nor do we commit to ensuring that the website remains available or that the material on the
						website is kept up to date.</p>
						<h2>Content Liability</h2>
						<p>We shall have no responsibility or liability for any content appearing on your Web site. You agree to indemnify
						and defend us against all claims arising out of or based upon your Website. No link(s) may appear on any
						page on your Web site or within any context containing content or materials that may be interpreted as
						libelous, obscene or criminal, or which infringes, otherwise violates, or advocates the infringement or
						other violation of, any third party rights.</p>
						<h2>Disclaimer</h2>
						<p>To the maximum extent permitted by applicable law, we exclude all representations, warranties and conditions relating to our website and the use of this website (including, without limitation, any warranties implied by law in respect of satisfactory quality, fitness for purpose and/or the use of reasonable care and skill). Nothing in this disclaimer will:</p>
						<ol>
						<li>limit or exclude our or your liability for death or personal injury resulting from negligence;</li>
						<li>limit or exclude our or your liability for fraud or fraudulent misrepresentation;</li>
						<li>limit any of our or your liabilities in any way that is not permitted under applicable law; or</li>
						<li>exclude any of our or your liabilities that may not be excluded under applicable law.</li>
						</ol>
						<p>The limitations and exclusions of liability set out in this Section and elsewhere in this disclaimer: (a)
						are subject to the preceding paragraph; and (b) govern all liabilities arising under the disclaimer or
						in relation to the subject matter of this disclaimer, including liabilities arising in contract, in tort
						(including negligence) and for breach of statutory duty.</p>
						<p>To the extent that the website and the information and services on the website are provided free of charge,
						we will not be liable for any loss or damage of any nature.</p>
						</p>
		         	</div>
		         	<div class="modal-footer justify-content-center">
		         		<div class="btn-group">
		         			<a href="logout.php" class="btn btn-xs btn-danger">I Don't Accept</a>
		         			<a href="actions.php?a=accept_terms" class="btn btn-xs btn-green">I Accept</a>
                    	</div>
                    </div>
		      	</div>
		   	</div>
		</div>
	</div>
	
	<!-- core js -->
	<script src="assets/js/app.min.js"></script>
	<script src="assets/js/theme/default.min.js"></script>

	<!-- datatables -->
	<script src="assets/plugins/datatables.net/js/jquery.dataTables.min.js"></script>
	<script src="assets/plugins/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
	<script src="assets/plugins/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
	<script src="assets/plugins/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
	<script src="assets/js/demo/table-manage-default.demo.js"></script>

	<!-- website tutorial -->
	<script type="text/javascript" src="assets/intro/intro.js"></script>

	<!-- select2 -->
	<script src="assets/plugins/select2/dist/js/select2.min.js" type="5963ed09217b30e2d65e0f4b-text/javascript"></script>

	<!-- apple switch -->
	<script src="assets/plugins/switchery/switchery.min.js" type="8c3720d2a681d0399123c034-text/javascript"></script>

	<?php if(!empty($_SESSION['alert']['status'])){ ?>
		<script>
			document.getElementById('status_message').innerHTML = '<div class="alert alert-<?php echo $_SESSION['alert']['status']; ?> fade show m-b-0"><?php echo $_SESSION['alert']['message']; ?></div>';
			setTimeout(function() {
				$('#status_message').fadeOut('fast');
			}, 5000);
		</script>
		<?php unset($_SESSION['alert']); ?>
	<?php } ?>

	<?php if( get( 'c' ) == '' || get( 'c' ) == 'home' ) { ?>
		<script type="text/javascript">
			function tutorial(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{ 
							intro: "Welcome to the Tutorial & Help guide. On each page there are buttons named 'Tutorial & Help' which will give you page specific information. If you get stuck on any page, please feel free to submit a support ticket."
						},
						{
							element: document.querySelector('.tutorial_total_clusters'),
							intro: "This shows how many Clusters you currently have setup with CloudShield.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_total_controllers'),
							intro: "This shows the total number of Controllers you have installed spread over all your Clusters.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_total_proxies'),
							intro: "This shows the total number of Proxies you have installed spread over all your Clusters.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_total_domains'),
							intro: "This shows how many Domains you currently have setup with CloudShield.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_news'),
							intro: "Get the very latest news and updates about the CloudShield platform.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'clusters' ) { ?>
		<script type="text/javascript">
			// data tables > table_clusters
			$(function () {
				$( '#table_clusters' ).DataTable({
					"order": [[ 0, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500, 1000, 5000, 10000, 25000, 50000, 100000],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			function tutorial(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_example_cluster'),
							intro: "What is a Cluster and how does it work.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_cluster_wizard'),
							intro: "CloudShield Cluster Design Wizard will help you to build a cluster that meets your needs.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_add_cluster'),
							intro: "Add a new Cluster.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'cluster_edit' ) { ?>
		<script type="text/javascript">
			// data tables > table_blocked_networks
			$(function () {
				$( '#table_blocked_networks' ).DataTable({
					"order": [[ 0, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": false,
					"searching": false,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			function tutorial_1(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_settings_cluster_name'),
							intro: "Give your Cluster a friendly easy to identify name.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_origin_domain'),
							intro: "Enter the domain name that this cluster will be protecting.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_origin_ip_address'),
							intro: "Enter the IP address of the origin server that this cluster will be protecting.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_origin_port'),
							intro: "Enter the TCP port of the origin server that this cluster will be protecting.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_cluster_notes'),
							intro: "You can enter notes about this Cluster. These notes are private and only visible to you. You should NOT enter sensative information such as passwords or login details here.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_1'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}

			function tutorial_2(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_settings_cache'),
							intro: "This setting allows you to enable or disable the content caching options. (this feature is currently not available)",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_stalker'),
							intro: "This setting allows you to enable or disable the Stalker / Ministra Portal that is available on some streaming platforms. This is a big security risk and should only be enabled if absolutely necessary.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_ip_fraud_protection'),
							intro: "This setting allows you to enable or disable the IP Fruad Protection system. This list contains over 1 billion IP addresses that are known to be under the control of scammers, fraudulent users and hackers.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_2'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}

			function tutorial_3(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_settings_ssl'),
							intro: "This will encrypt all traffic between the browser and your CloudShield Cluster. this is a great way to secure your content from attackers and those attempting to snoop on the traffic passing between the browser and your Cluster.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_ssl_out'),
							intro: "This will encrypt all traffic between your CloudShield Cluster and your origin server.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_3'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}

			function tutorial_4(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_settings_add_asn'),
							intro: "This section allows you to block entire networks by entering the ASN for each network you wish to block. When a Controller or Proxy is built or rebuilt, each ASN is queried and a full list of IP4 and IP6 network prefixes is gathered and added to the IP Route blackhole.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_blocked_asns'),
							intro: "This section lists the networks that your cluster will deny all access to.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_4'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}

			function tutorial_5(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_settings_evpn'),
							intro: "As part of the CloudShield service there is an Embedded VPN Server that runs on each Proxy server in your cluster. When this option enabled, your customers will be able to establish a dedicated OpenVPN connection directly to the Cluster.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_5'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'cluster' ) { ?>
		<script type="text/javascript">
			// data tables > table_controllers
			$(function () {
				$( '#table_controllers' ).DataTable({
					"order": [[ 0, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			// data tables > table_proxies
			$(function () {
				$( '#table_proxies' ).DataTable({
					"order": [[ 0, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			function tutorial_controllers(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					steps: [
						{
							element: document.querySelector('.tutorial_controllers'),
							intro: "Each Cluster needs at least one Controller. You can add a Controller here.",
							position: 'top'
						},
					]
				});

				intro.start();
			}

			function tutorial_proxies(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_proxy_add'),
							intro: "Each Cluster needs at least one Proxy. You can add a Proxy here.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_proxy_rebuild_all'),
							intro: "This option allows you to rebuild and update all Proxies at once.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'domain_names' ) { ?>
		<script type="text/javascript">
			// data tables > table_domain_names
			$(function () {
				$( '#table_domain_names' ).DataTable({
					"order": [[ 0, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			function tutorial(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					steps: [
						{
							element: document.querySelector('.tutorial_add_domain'),
							intro: "Each Cluster needs a domain name, add one here.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'domain_name' ) { ?>
		<script type="text/javascript">
			// data tables > table_dns_records
			$(function () {
				$( '#table_dns_records' ).DataTable({
					"order": [[ 0, "asc" ], [ 1, "asc" ]],
					"responsive": true,
					"columnDefs": [{
						"targets"  : 'no-sort',
						"orderable": false,
					}],
					"language": {
						"emptyTable": "No data found."
					},
					"oLanguage": {
						"sSearch": "Filter: "
					},
					"paging": true,
					"processing": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": false,
					"lengthMenu": [50, 100, 500, 1000, 5000, 10000, 25000, 50000, 100000],
					"pageLength": 50,
					search: {
					   search: '<?php if( isset( $_GET['search'] ) ) { echo $_GET['search']; } ?>'
					}
				});
			});

			function tutorial(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_add_dns_record'),
							intro: "You can add custom DNS records for this domain.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_resync_dns_records'),
							intro: "You can resync all Controller and Proxy DNS records.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_dns_records'),
							intro: "These are the DNS records for this domain. Controller and Proxy DNS records will appear here as you build your Cluster.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_dns_actions'),
							intro: "Each DNS record has additional options which can be accessed by click the Action button.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( get( 'c' ) == 'server_edit' ) { ?>
		<script type="text/javascript">
			function tutorial_1(){
				var intro = introJs();
				intro.setOptions({
					exitOnEsc: false,
					exitOnOverlayClick: false,
					showStepNumbers: false,
					showProgress: true,
					steps: [
						{
							element: document.querySelector('.tutorial_ssh_port'),
							intro: "Enter the SSH port for your server. (default: 22)",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_ssh_username'),
							intro: "Enter the SSH username for your server. (default: root)",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_ssh_password'),
							intro: "Enter the SSH password for your server.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_notes'),
							intro: "Enter any notes you wish to save about this server.",
							position: 'top'
						},
						{
							element: document.querySelector('.tutorial_settings_save_1'),
							intro: "Click here to save your changes.",
							position: 'top'
						},
					]
				});

				intro.start();
			}
		</script>
	<?php } ?>

	<?php if( $account_details['local_data']['accept_terms'] == 'no' ){ ?>
		<script>
		    $( window).on( 'load',function() {
                $( '#modal-terms' ).modal( { 
                        backdrop: 'static', 
                        keyboard: false, 
                    }
                );

                $( "#modal-terms" ).css( {
                    background:"rgb(0, 0, 0)",
                    opacity: ".50 !important",
                    filter: "Alpha(Opacity=50)",
                    width: "100%",
                } );
            } );
		</script>
	<?php } ?>
</body>
</html>