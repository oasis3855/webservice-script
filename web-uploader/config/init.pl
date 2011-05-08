#!/usr/bin/perl

# Web Uploader : configuration file

# IMPORTANT
# system copy command use $str_basedir + $arr_updirs[n]
# web access use $str_webaddr + $arr_updirs[n]

# web server URL
# [last char is not / (slash). example :'http://example.com' ]
our $str_webaddr = 'http://localhost';

# upload directory - <head part>, absolute directory of OS
# [last char is not / (slash). example :'/var/www' ]
our $str_basedir = '/var/www';

# upload directory - <tail part>, from web root
# [first char is / (shast). last char is not / (slash). example :'/blog/image' ]
our @arr_updirs = ('/test/photo-up',
			'/test/data');


# overwrite if same name file exist (0:OFF, 1:ON)
# user can change at web interface
our $flag_overwrite = 0;

# shrink image size of html <img> tag (pixels)
our $n_target_size = 320;
