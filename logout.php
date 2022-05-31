<?php

// include main functions
include( dirname(__FILE__).'/includes/core.php' );
include( dirname(__FILE__).'/includes/functions.php' );

// session destroy
session_destroy();

// redirect to index.php
go( 'index.php' );