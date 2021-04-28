#! /bin/bash

#######################################
# SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
# SPDX-License-Identifier: AGPL-3.0-only
#######################################

VERSION="1.36"

#
# Changelog
#
# Version 1.36
#  . Fix wrong group detection
#
# Version 1.35
#  . Read user and group from config.inc file
#
# Version 1.34
#  . Ignore tickets lang file
#
# Version 1.33
#  . Fix rollback with new vteversion file
#
# Version 1.32
#  . Exclude tabdata files from diff
#
# Version 1.31
#  . Support for vteversion file
#
# Version 1.30
#  . Fix for old md5sum versions
#
# Version 1.29
#  . Support for user supplied file hashes
#  . Maintenance mode set before backup
#
# Version 1.28
#  . Support for community edition
#
# Version 1.27
#  . Support for automatic updates
#
# Version 1.26
#  . Alert for old PHP version
#  . Added option to only delete files
#
# Version 1.25
#  . Fix accesskey parameter
#
# Version 1.24
#  . Ask to copy post patches if refusing rollback
#
# Version 1.23
#  . Rollback tries first to drop the old database
#
# Version 1.22
#  . Mysql backup also save triggers and events
#
# Version 1.21
#  . Mysql backup also save stored procedures
#
# Version 1.20
#  . Read safe update ranges from server
#  . Support for old files removal
#
# Version 1.19
#  . Added safe update ranges
#
# Version 1.18
#  . Support for new maintenance mode
#
# Version 1.17
#  . Minor bugfix
#
# Version 1.16
#  . Support for vteversion file
#
# Version 1.14 and 1.15
#  . Support for update notes
#
# Version 1.13
#  . Added patches cmd options
#
# Version 1.12
#  . Added custom tags option
#
# Version 1.11
#  . Fix for old tar command
#
# Version 1.10 and 1.9
#  . Fix file comparison bug
#
# Version 1.8
#  . Fix backup bug
#
# Version 1.7
#  . Added support for mysqli database
#
# Version 1.6
#  . Fixed VTE Url
#
# Version 1.5
#  . Adjusted minumum revision
#
# Version 1.4
#  . Fix parsing of username
#
# Version 1.3
#  . Fix detection of errors after update
#
# Version 1.2
#  . Fix for install folder
#  . Fix timeout issue for some updates
#
# Version 1.1
#  . Self update ability
#  . Some minor bugfixes
#
# Version 1.0
#  . First beta release
#
# Documentation : modules/SDK/doc/vteUpdater.pdf
#

# CONSTANTS

DEBUG_LEVEL_DEBUG=4
DEBUG_LEVEL_INFO=3
DEBUG_LEVEL_WARNING=2
DEBUG_LEVEL_ERROR=1
RETCODE_OK=0
RETCODE_FAIL=1
RETCODE_BREAK=2
RETCODE_OKROLLBACK=3

CRMVILLAGE_VTE="https://partner.vtecrm.net/"
UPDATE_SERVER="https://autoupdate.vtecrm.net/"
UPDATE_SERVER_CE="https://autoupdatece.vtecrm.net/"


# CONFIG VARIABLES
[ -t 1 ] && USECOLORS=1 || USECOLORS=0
DEBUG=$DEBUG_LEVEL_INFO
LOGDEBUG=$DEBUG_LEVEL_DEBUG
VTEDIR=./
WORKDIR="$VTEDIR"vte_updater/
PACKAGESDIR="$WORKDIR"packages/
BACKUPDIR="$WORKDIR"backup/
TEMPDIR="$WORKDIR"temp/
# these patterns are used to find files that shouldn't be checked
EXCLUDE_CHECK=(
	"tabdata.php"
	"parent_tabdata.php"
	".*ckeditor/lang/.*"
	".*jscalendar/lang/.*"
	".*plupload/i18n/.*"
	".*dataTables/i18n/.*"
	# ignore some languages, since there were problems in old releases
	"modules/HelpDesk/language/*.lang.php"
	".*de_de.lang.php"
	".*de_de.lang.js"
	".*lang_de_de.js"
	".*pt_br.lang.php"
	".*pt_br.lang.js"
	".*lang_pt_br.js"
	".*nl_nl.lang.php"
	".*nl_nl.lang.js"
	".*lang_nl_nl.js"

)

# GLOBAL VARIABLES
STATUS=""
VTEREVISION=0
ISCOMMUNITY=0
REVRANGES=""
VTEURL=""
VTEBRANCH=""
LOGFILE=""
DIFFLOG=""
UPDATELOG=""
NOTESLOG=""
RECFILE=""
DESTREVISION=0
HTTPD_USER="www-data"
HTTPD_GROUP="www-data"
BACKUPFILE=""
BACKUPDELFILE=""
BACKUPDBFILE=""
PACKAGEFILE=""
SRCFILE=""
SRCFILE_DIR=""
DELFILE=""
DELFILE_DIR=""
PACKAGE_DIR=""
SELFUPDATE="vteSelfUpdate.php"
EXTRATAGS=()
EXTRATAGSFILE=""
PREPATCHDIR=""
POSTPATCHDIR=""
FILEHASHES=""
ROLLBACKOK=0

USERNAME=""
ACCESSKEY=""
HASHEDKEY=""

# command line flags
WWWUSERS=""
SKIPFILECHECK=0
SKIPFILEREMOVE=0
SKIPDBACKUP=0
SKIPVTEBACKUP=0
SKIPCRON=0
ONLYBACKUP=0
ONLYROLLBACK=0
ONLYDELETE=0
ONLYFILECHECK=0
SKIPUPGRADE=0
FORCEUPGRADE=0
ONLYUPGRADE=0
USELOCALPKG=""
USELOCALSRCPKG=""
USELOCALDELPKG=""
ENABLEVTE=0
BATCH=0

# compatibility_opts
COMP_TAR_VCS=1

# FUNCTIONS
write_log () {
	local now=$(date +"%Y-%m-%d %H:%M:%S")
	if [ -n "$LOGFILE" -a -w "$LOGFILE" ]; then
		echo "[$now] $1" >> "$LOGFILE"
	fi
}

write_rec () {
	local now=$(date +"%Y-%m-%d %H:%M:%S")
	if [ -n "$RECFILE" -a -w "$RECFILE" ]; then
		echo "[$now] $1" >> "$RECFILE"
	fi
}

print_text () {
	echo $1
}

debug () {
	[ $DEBUG -ge $DEBUG_LEVEL_DEBUG ] && print_text "${COLOR_WHITE}[DEBUG]${COLOR_NORMAL} $1"
	[ $LOGDEBUG -ge $DEBUG_LEVEL_DEBUG ] && write_log "[DEBUG] $1"
}

info () {
	[ $DEBUG -ge $DEBUG_LEVEL_INFO ] && print_text "${COLOR_CYAN}[INFO]${COLOR_NORMAL} $1"
	[ $LOGDEBUG -ge $DEBUG_LEVEL_INFO ] && write_log "[INFO] $1"
}

warning () {
	[ $DEBUG -ge $DEBUG_LEVEL_WARNING ] && print_text "${COLOR_YELLOW}[WARNING]${COLOR_NORMAL} $1"
	[ $LOGDEBUG -ge $DEBUG_LEVEL_WARNING ] && write_log "[WARNING] $1"
}

error () {
	[ $DEBUG -ge $DEBUG_LEVEL_ERROR ] && print_text "${COLOR_RED}[ERROR]${COLOR_NORMAL} $1"
	[ $LOGDEBUG -ge $DEBUG_LEVEL_ERROR ] && write_log "[ERROR] $1"
}

init_colors () {
	# first set all colors to 0
	COLOR_BOLD=""
	COLOR_UNDERLINE=""
	COLOR_STANDOUT=""
	COLOR_NORMAL=""
	COLOR_BLACK=""
	COLOR_RED=""
	COLOR_GREEN=""
	COLOR_YELLOW=""
	COLOR_BLUE=""
	COLOR_MAGENTA=""
	COLOR_CYAN=""
	COLOR_WHITE=""

	if [ $USECOLORS -eq 0 ]; then
		return
	fi

	# then check if we are using a real terminal and get the color codes
	command -v "tput" > /dev/null 2>&1 && [ -t 1 ]; {
		local NCOL=$(tput colors)
		if [ -n "$NCOL" -a $NCOL -ge 8 ]; then
			COLOR_BOLD="$(tput bold)"
			COLOR_UNDERLINE="$(tput smul)"
			COLOR_STANDOUT="$(tput smso)"
			COLOR_NORMAL="$(tput sgr0)"
			COLOR_BLACK="$(tput setaf 0)"
			COLOR_RED="$(tput setaf 1)"
			COLOR_GREEN="$(tput setaf 2)"
			COLOR_YELLOW="$(tput setaf 3)"
			COLOR_BLUE="$(tput setaf 4)"
			COLOR_MAGENTA="$(tput setaf 5)"
			COLOR_CYAN="$(tput setaf 6)"
			COLOR_WHITE="$(tput setaf 7)"
		fi
	}
}

version_lte() {
    [ "$1" = "$(echo -e "$1\n$2" | sort -V | head -n1)" ]
}

version_lt() {
    [ "$1" = "$2" ] && return 1 || version_lte $1 $2
}

# reads a password
ask_pwd () {
	# $1 = text

	local PWD=""

	echo -n "$1: "
	while IFS= read -r -s -n 1 char; do
		if [[ $char == $'\0' ]]; then
			echo ""
			break
		elif [[ $char == $'\177' ]]; then
			if [ -n "$PWD" ]; then
				echo -e -n '\b \b'
				PWD="${PWD%?}"
			fi
		else
			echo -n "*"
			PWD="${PWD}${char}"
		fi
	done
	write_rec "$1: *****"
	write_log "$1: *****"
	_RET="$PWD"
}

ask () {
	# $1 = question
	# $2 = possible answers
	# $3 = convert to lowercase (1/0, default =1)

	local question="$1 "
	local lcconvert=1

	if [ -n "$3" -a "$3" = "0" ]; then
		lcconvert=0
	fi

	if [ -n "$2" ]; then
		question="$question""($2) "
	fi
	question="$question"": "
	echo ""
	echo -n "$question"

	ans=""

	while [ -z "$ans" ]; do
		# read the answer
		read ans
		if [ -z "$ans" ]; then
			echo -n "Please answer $2 : "
		fi
	done

	# transform to lowercase
	if [ $lcconvert -eq 1 ]; then
		ans=$(echo "$ans" | tr '[:upper:]' '[:lower:]')
	fi

	write_rec "$question $ans"
	write_log "$question $ans"
	_RET=$ans
}

is_valid_user () {
	# $1 = username to test
	# return 0=invalid
	_RET=0
	id -u "$1" 1>/dev/null 2>&1 && _RET=1
}

is_valid_group () {
	# $1 = groupname to test
	# return 0=invalid
	_RET=0
	getent group "$1" 1>/dev/null 2>&1 && _RET=1
}

# retrieves the user and group of the webserver
# this is a very rough way to do things
get_httpd_user () {

	local USR=""
	local GRP=""

	if [ -n "$WWWUSERS" ]; then
		USR=$(echo -n $WWWUSERS | cut -f 1 -d ":")
		GRP=$(echo -n $WWWUSERS | cut -f 2 -d ":")
		is_valid_user "$USR"
		if [ $_RET -eq 0 ]; then
			error "The chosen user doesn't exist on this system"
			exit $RETCODE_FAIL
		fi
		is_valid_group "$GRP"
		if [ $_RET -eq 0 ]; then
			error "The chosen group doesn't exist on this system"
			exit $RETCODE_FAIL
		fi
		HTTPD_USER=$USR
		HTTPD_GROUP=$GRP
		debug "Using $HTTPD_USER:$HTTPD_GROUP as user and group for files"
		return
	fi

	debug "Detecting web server user and group..."

	# test for config.inc.php
	USR=$(php -r 'require("config.inc.php"); if (is_array($new_folder_storage_owner)) echo $new_folder_storage_owner["user"];')
	GRP=$(php -r 'require("config.inc.php"); if (is_array($new_folder_storage_owner)) echo $new_folder_storage_owner["group"];')

	# test for apache
	if [ -z "$USR" -a -r "/etc/apache2/envvars" ]; then
		USR=$(bash -c 'source /etc/apache2/envvars && echo $APACHE_RUN_USER')
	fi
	if [ -z "$GRP" -a -r "/etc/apache2/envvars" ]; then
		GRP=$(bash -c 'source /etc/apache2/envvars && echo $APACHE_RUN_GROUP')
	fi

	# test for apache on CentOS
	if [ -z "$USR" -a -r "/etc/httpd/conf/httpd.conf" ]; then
		USR=$(grep "^User " /etc/httpd/conf/httpd.conf | sed 's/User\s*//')

	fi
	if [ -z "$GRP" -a -r "/etc/httpd/conf/httpd.conf" ]; then
		GRP=$(grep "^Group " /etc/httpd/conf/httpd.conf | sed 's/Group\s*//')
	fi

	# TODO: other systems, other webservers...

	# fallback test on file
	if [ -z "$USR" ]; then
		USR=$(stat -c '%U' "$VTEDIR"config.inc.php)
	fi
	if [ -z "$GRP" ]; then
		GRP=$(stat -c '%G' "$VTEDIR"config.inc.php)
	fi

	if [ -n "$USR" -a -n "$GRP" ]; then
		is_valid_user "$USR"
		if [ $_RET -eq 1 ] ; then HTTPD_USER=$USR; fi
		is_valid_group "$GRP"
		if [ $_RET -eq 1 ] ; then HTTPD_GROUP=$GRP; fi
	fi

	if [ $BATCH -eq 0 ]; then
		ask "The user and group for the web server have been detected as $HTTPD_USER:$HTTPD_GROUP. Is this correct?" "Y/N"
		if [ "$_RET" = 'n' ]; then
			while true; do
				ask "Please type the user" ""
				USR=$_RET
				is_valid_user "$USR"
				if [ $_RET -eq 1 ]; then
					break
				else
					warning "The chosen user doesn't exist on this system"
				fi
			done
			while true; do
				ask "Please type the group" ""
				GRP=$_RET
				is_valid_group "$GRP"
				if [ $_RET -eq 1 ]; then
					break
				else
					warning "The chosen group doesn't exist on this system"
				fi
			done
			HTTPD_USER=$USR
			HTTPD_GROUP=$GRP
		fi
	fi

	debug "Using $HTTPD_USER:$HTTPD_GROUP as user and group for files"
}

check_command () {
	debug "Checking $1..."
	command -v "$1" > /dev/null 2>&1 || { error "Command $1 not found."; exit $RETCODE_FAIL; }
}

check_tar_opts () {
	debug "Checking tar options..."
	# check exclude-vcs options, which is not available in old tar
	tar --exclude-vcs --version 1>/dev/null 2>/dev/null || COMP_TAR_VCS=0
}

check_commands_comp () {
	check_tar_opts
}

check_vtedir () {
	debug "Checking VTE directory..."
	if [ -d "$1" -a -w "$1" ]; then
		# do some write tests
		if [ -f "$1"config.inc.php -a -f "$1"/index.php -a -f "$1"/modules/Update/Update.php ]; then
			if [ -w "$1"config.inc.php -a -w "$1"/index.php -a -w "$1"/modules ]; then
				:
			else
				error "Some files are not writable, please check permissions first"
				exit $RETCODE_FAIL;
			fi
			if [ -s "$1"config.inc.php -a ! -d "$1"/install -a ! -f "$1"/install.php ]; then
				:
			else
				error "VTE is not correctly installed. Please complete the installation process first, or check if the install/ folder is still around."
				exit $RETCODE_FAIL;
			fi
		else
			error "Directory $1 doesn't seem a valid VTE directory"
			exit $RETCODE_FAIL;
		fi
	else
		error "VTE Directory $1 is not writable"
		exit $RETCODE_FAIL;
	fi

	# check if it's a community edition
	if [ -f "$1"vteversion.php -a -r "$1"vteversion.php ]; then
		VERSIONFILE="vteversion.php"
	else
		VERSIONFILE="vtigerversion.php"
	fi
	grep -q -i -F "VTENEXTCE" "$1""$VERSIONFILE" && {
		ISCOMMUNITY=1
	}
}

create_workdir () {
	debug "Creating working directory..."
	mkdir -p "$1" >/dev/null 2>&1
	if [ ! -w "$WORKDIR" ]; then
		error "Unable to create working directory $1"
		exit $RETCODE_FAIL
	fi
	touch "$1"testfile
	if [ ! -r "$1"testfile ]; then
		error "Working directory $1 is not writable"
		exit $RETCODE_FAIL
	fi
	rm -f "$1"testfile >/dev/null 2>&1
	mkdir -p "$PACKAGESDIR"

	# create .htaccess
	echo "Deny from all" > "$WORKDIR"".htaccess"

	# create index.php
	echo '<?php header("Location: ../"); ' > "$WORKDIR""index.php"
}

extract_php_global_var () {
	# $1 = php file
	# $2 = variable name
	if [ ! -r "$1" ]; then
		error "PHP file $1 not found"
		exit $RETCODE_FAIL
	fi
	# escape variable name (so i can search for arrays also)
	local varname=$(echo -n "$2" | sed 's/[][]/\\\0/g')

	local ROW=$(grep -m 1 -o -E "^[[:space:]]*\\\$$varname[[:space:]]*=[[:space:]]*['\"]?.*['\"]?[[:space:]]*;" "$1")
	local VALUE=$(echo -n "$ROW" | sed -r "s/.*\\\$$varname[[:space:]]*=[[:space:]]*['\"]?//" | sed -r "s/['\"]?[[:space:]]*;\$//" )
	_RET=$VALUE
}

extract_php_version_var () {
	# $1 = vtedir
	# $2 = variable name
	if [ -f "$1"vteversion.php -a -r "$1"vteversion.php ]; then
		extract_php_global_var "$1"vteversion.php "$2"
	elif [ -f "$1"vtigerversion.php -a -r "$1"vtigerversion.php ]; then
		extract_php_global_var "$1"vtigerversion.php "$2"
	else
		error "Unable to find vteversion file";
		exit $RETCODE_FAIL
	fi
}

get_vte_revision () {
	extract_php_version_var "$1" enterprise_current_build
	VTEREVISION=$_RET
	if [ -z "$VTEREVISION" ]; then
		error "Unable to retrieve VTE revision"
		exit $RETCODE_FAIL
	elif [ $VTEREVISION -lt 840 ]; then
		error "VTE is too old"
		exit $RETCODE_FAIL
	fi

	# extract the branch
	extract_php_version_var "$1" enterprise_current_version
	VTEBRANCH=$_RET
	if [ "$VTEBRANCH" = "4.5" ]; then
		error "VTE 4.5 is not supported"
		exit $RETCODE_FAIL
	fi

	# extract also vte url
	extract_php_global_var "$1"config.inc.php site_URL
	VTEURL="$_RET"

	# replace the update server if community
	if [ $ISCOMMUNITY -eq 1 ]; then
		UPDATE_SERVER=$UPDATE_SERVER_CE
	fi
}

init_subfolder () {
	mkdir -p "$WORKDIR""$VTEREVISION"
	if [ -w "$WORKDIR""$VTEREVISION" ]; then
		WORKDIR="$WORKDIR""$VTEREVISION"/
		BACKUPDIR="$WORKDIR"backup/
		TEMPDIR="$WORKDIR"temp/
	else
		error "Unable to create folder ${WORKDIR}${VTEREVISION}"
		exit $RETCODE_FAIL
	fi
	mkdir -p "$BACKUPDIR"
	mkdir -p "$TEMPDIR"
}

init_log () {
	local now=$(date +"%Y%m%d-%H%M%S")
	local LOGNAME="$now""_update_"$VTEREVISION".log"
	local RECNAME="$now""_rec_"$VTEREVISION".rec"
	LOGFILE="$WORKDIR"$LOGNAME
	RECFILE="$WORKDIR"$RECNAME
	touch "$LOGFILE"
	if [ ! -w "$LOGFILE" ]; then
		error "Unable to log to file $LOGFILE"
		exit $RETCODE_FAIL
	fi
	debug "Logging to $LOGFILE"

	touch "$RECFILE"
	# log for differences, truncate
	DIFFLOG="$WORKDIR""differences.log"
	echo -n "" > "$DIFFLOG"
}

unpack_src () {
	# $1 = source file

	if [ $SKIPFILECHECK -eq 1 ]; then
		return
	fi

	debug "Unpacking source files..."
	SRCFILE_DIR="$WORKDIR"src/
	mkdir -p "$SRCFILE_DIR"
	if [ ! -w "$SRCFILE_DIR" ]; then
		error "Folder $SRCFILE_DIR is not writable"
		exit $RETCODE_FAIL
	fi
	# do cleanup
	rm -rf "$SRCFILE_DIR"*
	# unpack
	tar -xf "$1" -C "$SRCFILE_DIR" --strip=1
	rm -f "$SRCFILE_DIR""vteUpdater.sh" 2>/dev/null
	fix_permissions "$SRCFILE_DIR"
}

unpack_update () {
	# $1 = update file
	debug "Unpacking update files..."
	PACKAGE_DIR="$WORKDIR"files"$DESTREVISION"/
	mkdir -p "$PACKAGE_DIR"
	if [ ! -w "$PACKAGE_DIR" ]; then
		error "Folder $PACKAGE_DIR is not writable"
		exit $RETCODE_FAIL
	fi
	# do cleanup
	rm -rf "$PACKAGE_DIR"*
	# unpack
	tar -xf "$1" -C "$PACKAGE_DIR" --strip=1
	rm -f "$PACKAGE_DIR""vteUpdater.sh" 2>/dev/null
	rm -rf "$PACKAGE_DIR"install "$PACKAGE_DIR"install.php 2>/dev/null
	fix_permissions "$PACKAGE_DIR"
}

unpack_del () {
	# $1 = file

	if [ $SKIPFILEREMOVE -eq 1 -o ! -s "$DELFILE" ]; then
		# do nothing
		return
	fi

	debug "Unpacking deleted files list ..."
	DELFILE_DIR="$WORKDIR"del/
	mkdir -p "$DELFILE_DIR"
	if [ ! -w "$DELFILE_DIR" ]; then
		error "Folder $DELFILE_DIR is not writable"
		exit $RETCODE_FAIL
	fi
	# do cleanup
	rm -rf "$DELFILE_DIR"*
	# unpack
	tar -xf "$1" -C "$DELFILE_DIR" --strip=1
	fix_permissions "$DELFILE_DIR"
}

check_files_md5 () {
	# $1 = file 1
	# $2 = file 2
	# return 1 = equal, 0 = differs

	# md5 che
	local hash1=$(md5sum "$1" | cut -f 1 -d " ")
	local hash2=$(md5sum "$2" | cut -f 1 -d " ")

	if [ "$hash1" = "$hash2" ]; then
		_RET=1
	else
		_RET=0
	fi
}

check_files_lines () {
	# $1 = file 1
	# $2 = file 2
	# return 1 = equal, 0 = differs

	debug "Checking file $1 line by line..."
	_RET=1

	# NEW FASTER CODE
	# now trim extra spaces, remove blank lines and make sure file ends with newline
	sed -e 's/^\s*\|\s*$//g' -e '/^$/d' -e '$a\' "$1" > "$TEMPDIR"diff_file_1
	sed -e 's/^\s*\|\s*$//g' -e '/^$/d' -e '$a\' "$2" > "$TEMPDIR"diff_file_2
	cmp -s "$TEMPDIR"diff_file_1 "$TEMPDIR"diff_file_2 || _RET=0

	# OLD CODE: WORKS, BUT IT'S EXTREMELY SLOW
# 	while true; do
# 		# go on until we find a non empty line
# 		while read -r -u 3 line1; do
# 			line1=$(echo -n "$line1" | sed 's/^[\r\n\t ]*\|[\r\n\t ]*$//g')
# 			if [ -n "$line1" ]; then break; fi
# 		done
# 		while read -r -u 4 line2; do
# 			line2=$(echo -n "$line2" | sed 's/^[\r\n\t ]*\|[\r\n\t ]*$//g')
# 			if [ -n "$line2" ]; then break; fi
# 		done
#
# 		# end of files
# 		if [ -z "$line1" -a -z "$line2" ]; then break; fi
#
# 		# here I have 2 non empty lines
# 		if [ "$line1" = "$line2" ]; then
# 			# empty or difference of spaces
# 			continue
# 		else
# 			_RET=0
# 			break
# 		fi
# 	done 3<"$1" 4<"$2"
}

check_mycrmv_tags () {
	# $1 = file to check
	debug "Checking file $1 for custom tags..."

	local LINE=$(grep -l -i -F 'mycrmv@' "$1")
	_RET=1
	if [ -n "$LINE" ]; then
		_RET=0
		return
	fi

	if [ ${#EXTRATAGS[@]} -gt 0 -a -n "$EXTRATAGSFILE" ]; then
		debug "Checking file $1 for additional tags..."

		LINE=$(grep -l -i -F -f "$EXTRATAGSFILE" "$1")
		if [ -n "$LINE" ]; then
			_RET=0
		fi
	fi
}

check_single_file () {
	# $1 = file in VTE folder that will be overwritten
	# $2 = file in Source folder
	# return 1 = equal, 0 = differs

	check_files_md5 "$1" "$2"
	if [ "$_RET" = "1" ]; then
		_RET=1
		return
	fi

	check_mycrmv_tags "$1"
	if [ "$_RET" = "0" ]; then
		_RET=0
		return
	fi

	check_files_lines "$1" "$2"
	if [ "$_RET" = "1" ]; then
		_RET=1
		return
	fi

	_RET=0
}

check_package () {
	# $1 = package file
	debug "Checking package $1..."

	_RET=1

	local BASENAME=$(basename "$1")
	local MODULE=${BASENAME%.zip}
	local SRCPACK="$SRCFILE_DIR""$1"
	local VTEFILE=""
	local SRCFILE=""
	local NEWSRCFILE=""
	local SKIP=0

	if [ -f "$SRCPACK" ]; then
		# unpack source
		PACKLIST=$(zipinfo -1 "$SRCPACK")
		unzip -q -o "$SRCPACK" -d "$SRCFILE_DIR" -x '*manifest.xml'
		if [ $? -gt 0 ]; then
			error "Error during extraction of $1"
			exit $RETCODE_FAIL
		fi
		# now check every file extracted
		for zf in $PACKLIST; do
			if [ "$zf" = 'manifest.xml' ]; then continue; fi

			SRCFILE="$SRCFILE_DIR""$zf"

			# check for special folders
			NEWSRCFILE=""
			if [[ "$zf" =~ ^cron/ ]]; then
				zf="cron/modules/$MODULE/"${zf#cron/}
				NEWSRCFILE="$SRCFILE_DIR""$zf"
			elif [[ "$zf" =~ ^templates/ ]]; then
				zf="Smarty/templates/modules/$MODULE/"${zf#templates/}
				NEWSRCFILE="$SRCFILE_DIR""$zf"
			fi

			# and move the file to the right place
			if [ -n "$NEWSRCFILE" -a -f "$SRCFILE" ]; then
				mkdir -p $(dirname "$NEWSRCFILE")
				mv -f "$SRCFILE" "$NEWSRCFILE"
			fi

			VTEFILE="$VTEDIR""$zf"
			SRCFILE="$SRCFILE_DIR""$zf"

			if [[ "$zf" =~ ^[^/\\]+zip$ ]]; then
				# sub package
				check_package "$zf"
				continue
			fi

			SKIP=0
			for exre in ${EXCLUDE_CHECK[@]}; do
				if [[ "$zf" =~ $exre ]]; then
					SKIP=1
					break
				fi
			done
			if [ $SKIP -eq 1 ]; then
				debug "Skipping file $VTEFILE"
				continue
			fi

			if [ -f "$VTEFILE" ]; then
				check_single_file "$VTEFILE" "$SRCFILE"
				if [ "$_RET" = "1" ]; then
					:
					# equal
				elif [ "$_RET" = "0" ]; then
					echo "$VTEFILE" >> "$DIFFLOG"
				fi
			fi
		done
	fi
}

check_update_files () {

	if [ $SKIPFILECHECK -eq 1 ]; then
		warning "File check skipped as specified on command line"
		return
	fi

	info "Starting file comparison..."
	local LIST=$(find "$PACKAGE_DIR" -type f)
	local LASTDIR=$(basename "$PACKAGE_DIR")
	local VTEFILE=''
	local VTEFILERE=''
	local SRCFILE=''
	local PACKS=''
	local SKIP=0
	local MD5LIST=''
	local MD5FOUND=''

	if [ -n "$FILEHASHES" ]; then
		if [ ! -r "$FILEHASHES" ]; then
			error "The provided file $FILEHASHES is not readable"
			exit $RETCODE_FAIL
		fi
		# check if the ignore-missing option is supported
		md5sum --help | grep -q -F -- "--ignore-missing" && {
			MD5LIST=$(LC_ALL=C md5sum --ignore-missing -c "$FILEHASHES" 2>/dev/null)
		} || {
			MD5LIST=$(LC_ALL=C md5sum -c "$FILEHASHES" 2>/dev/null)
		}
	fi

	# prepare the file with the extra tags
	if [ ${#EXTRATAGS[@]} -gt 0 ]; then
		EXTRATAGSFILE=$(mktemp)
		printf '%s\n' "${EXTRATAGS[@]}" > "$EXTRATAGSFILE"
		local EXTRAPLIST=$(printf ", %s" "${EXTRATAGS[@]}")
		EXTRAPLIST=${EXTRAPLIST:1}
		info "Additional tags to be searched: $EXTRAPLIST"
	else
		EXTRATAGSFILE=""
	fi

	for f in $LIST; do
		# strip first part
		f=$(echo -n "$f" | sed "s#.*$LASTDIR/##")
		if [[ "$f" =~ ^packages/ ]]; then
			PACKS=$PACKS" $f"
			continue
		fi
		VTEFILE="$VTEDIR""$f"
		SRCFILE="$SRCFILE_DIR""$f"
		# check if I should exclude it from the check
		SKIP=0
		for exre in ${EXCLUDE_CHECK[@]}; do
			if [[ "$f" =~ $exre ]]; then
				SKIP=1
				break
			fi
		done
		if [ $SKIP -eq 1 ]; then
			debug "Skipping file $VTEFILE"
			continue
		fi

		if [ -f "$VTEFILE" ]; then

			# check for passed md5 list
			if [ -n "$MD5LIST" ]; then
				VTEFILERE=$(echo -n "$VTEFILE" | sed 's|[.]|\\\0|g')
				MD5FOUND=$(echo "$MD5LIST" | grep "^${VTEFILERE}: ")
				if [ -n "$MD5FOUND" ]; then
					# ok, was one in the md5 file
					echo "$MD5FOUND" | grep -q ' OK$' && {
						# the hash is good, ignore file
						:
					} || {
						# the hash is different, show it
						echo "$VTEFILE" >> "$DIFFLOG"
					}
					# and don't do any other check!
					continue
				fi
			fi

			if [ ! -r "$SRCFILE" ]; then
				debug "File $f is not in source files, probably it's a new file, checking only tags"
				check_mycrmv_tags "$VTEFILE"
				if [ "$_RET" = "0" ]; then
					echo "$VTEFILE" >> "$DIFFLOG"
				fi
				continue
			fi
			check_single_file "$VTEFILE" "$SRCFILE"
			if [ "$_RET" = "1" ]; then
				:
				# equal
			elif [ "$_RET" = "0" ]; then
				echo "$VTEFILE" >> "$DIFFLOG"
			fi
		fi
	done
	# now check packages
	for p in $PACKS; do
		check_package $p
	done

	# remove the temp file
	if [ -f "$EXTRATAGSFILE" ]; then
		rm -rf "$EXTRATAGSFILE"
	fi

	# check for file differences
	if [ -s "$DIFFLOG" ]; then
		warning "Some differences were found in VTE. These are the modified files. Check the log in $DIFFLOG"
		cat "$DIFFLOG"
		if [ $ONLYFILECHECK -eq 0 -a $BATCH -eq 0 ]; then
			ask "Do you want to continue (if you answer YES you may loose some customizations)" "Y/N"
			if [ "$_RET" = 'n' ]; then
				info "Update interrupted"
				exit $RETCODE_OK
			fi
		fi
	else
		info "No differences found"
	fi

	if [ $ONLYFILECHECK -eq 1 ]; then

		exit $RETCODE_OK
	fi
}

create_vte_backup () {

	if [ $SKIPVTEBACKUP -eq 1 ]; then
		warning "VTE files backup skipped as specified on command line"
		return
	fi

	info "Creating backup of VTE files..."

	if [ ! -w "$BACKUPDIR" ]; then
		error "Backup directory $BACKUPDIR is not writable"
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi

	local now=$(date +"%Y%m%d-%H%M%S")
	local BAKNAME="$now""_files.tgz"
	BACKUPFILE="$BACKUPDIR""$BAKNAME"
	if [ $COMP_TAR_VCS -eq 1 ]; then
		tar --exclude-vcs --exclude "vte_updater" --exclude "storage" --exclude "vteUpdater.sh" -czf "$BACKUPFILE" "$VTEDIR"
	else
		tar --exclude ".git" --exclude ".svn" --exclude "vte_updater" --exclude "storage" --exclude "vteUpdater.sh" -czf "$BACKUPFILE" "$VTEDIR"
	fi
	if [ $? -gt 0 ]; then
		error "There was an error during backup."
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi
	if [ ! -s "$BACKUPFILE" ]; then
		error "There was an error during backup."
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi
	debug "Backup of files created in $BACKUPFILE"
}

get_db_config () {
	# global vars because i need them later
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_server.]"
	DBHOST=$_RET
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_port.]"
	DBPORT=$_RET
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_username.]"
	DBUSER=$_RET
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_password.]"
	DBPWD=$_RET
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_type.]"
	DBTYPE=$_RET
	extract_php_global_var "${VTEDIR}config.inc.php" "dbconfig[.db_name.]"
	DBNAME=$_RET

	# clean variables
	DBPORT=$(echo -n "$DBPORT" | tr -d ":")
	if [ -z "$DBPORT" ]; then
		DBPORT=3306
	fi
}

create_db_backup () {

	if [ $SKIPDBACKUP -eq 1 ]; then
		warning "Database backup skipped as specified on command line"
		return
	fi

	if [ $ONLYBACKUP -eq 0 -a $BATCH -eq 0 ]; then
		ask "Do you want to backup the database?" "Y/N"
		if [ "$_RET" = 'n' ]; then
			ask "Without a backup you won't be able to restore the VTE in case of errors, answer 'Y' only if you already have a backup. Are you sure?" "Y/N"
			if [ "$_RET" = 'y' ]; then
				warning "Database backup skipped by user"
				return
			fi
		fi
	fi

	info "Creating backup of database..."

	get_db_config

	if [ "$DBTYPE" != "mysql" -a "$DBTYPE" != "mysqli" ]; then
		error "The database type ($DBTYPE) is not supported for automatic backup. Please do the backup manually, then skip this step."
		clean_vte_backup
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi

	check_command mysql
	check_command mysqldump

	local now=$(date +"%Y%m%d-%H%M%S")
	local BAKNAME="$now""_db.sql.gz"
	BACKUPDBFILE="$BACKUPDIR""$BAKNAME"

	mysqldump --default-character-set=utf8 --single-transaction --quick --routines --triggers --events --add-drop-table \
		-h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" "$DBNAME" | gzip > "$BACKUPDBFILE"
	if [ ${PIPESTATUS[0]} -gt 0 -o ${PIPESTATUS[1]} -gt 0 ]; then
		error "Error during database backup"
		clean_vte_backup
		clean_db_backup
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi

	debug "Backup of database created in $BACKUPDBFILE"
}

clean_vte_backup () {
	debug "Removing backup of VTE files..."

	if [ -r "$BACKUPFILE" ]; then
		rm -f "$BACKUPFILE"
	fi
}

clean_db_backup () {
	debug "Removing backup of database..."

	if [ -r "$BACKUPDBFILE" ]; then
		rm -f "$BACKUPDBFILE"
	fi
}

# WARNING!! Use this function only for testing
clean_all_backups () {
	debug "Removing all existing backups..."

	rm -rf "$BACKUPDIR"*
}

set_file_ownership () {
	# $1 = dir or file
	debug "Setting ownership to $HTTPD_USER:$HTTPD_GROUP for $1..."

	chown -fR "$HTTPD_USER":"$HTTPD_GROUP" "$1" || warning "Unable to set ownership of $1"
}

fix_permissions () {
	# $1 = dir or file
	set_file_ownership "$1"
}

# detects if the file lines end with CRLF or LF
# return 1 if it's CRLF
is_dos_eol () {
	# $1 = filename
	_RET=0
	[[ $(head -1 "$1") == *$'\r' ]] && _RET=1
}

is_vte_disabled () {
	_RET=0
	# check new method
	if [ -f "$VTEDIR"maintenance.php ]; then
		grep -q -E '^\$vte_maintenance\s*=\s*(true|1);' "$VTEDIR"maintenance.php && _RET=1
	else
		grep -q -F "VTEUPDATE_DISABLED" "$VTEDIR"config.inc.php && _RET=1
	fi
}

disable_vte () {
	debug "Disabling VTE..."

	# check if already disabled
	is_vte_disabled
	if [ $_RET -eq 1 ]; then
		debug "VTE is already disabled."
		return
	fi

	# new method
	if [ -w "$VTEDIR"maintenance.php ]; then
		sed -i 's/^\$vte_maintenance.*$/$vte_maintenance = true;/i' "$VTEDIR"maintenance.php
	else

		# preserve EOL style
		local EOL='\n'
		is_dos_eol "$VTEDIR"config.inc.php
		if [ $_RET -eq 1 ]; then
			EOL='\r\n'
		fi

		sed -i 's/^<?php/\0'$EOL'\/\* VTEUPDATE_DISABLED \*\/ global $vte_self_update; if (PHP_SAPI != "cli" || !$vte_self_update) die("VTE is in maintenance mode");/i' "$VTEDIR"config.inc.php
	fi
}

enable_vte () {
	debug "Enabling VTE..."

	# new method
	if [ -w "$VTEDIR"maintenance.php ]; then
		sed -i 's/^\$vte_maintenance.*$/$vte_maintenance = false;/i' "$VTEDIR"maintenance.php
	fi
	# and just to be sure, enable it in the old way
	sed -i '/VTEUPDATE_DISABLED/d' "$VTEDIR"config.inc.php
}

enable_cron () {
	if [ ! -w "/etc/crontab" ]; then
		warning "Crontab file is not writable. Please enable cron processes manually"
	fi

	sed -i "s/### VTEUPDATE ### //g" /etc/crontab
	debug "Cron processes enabled"
}

disable_cron () {
	if [ ! -w "/etc/crontab" ]; then
		warning "Crontab file is not writable. Please make sure to stop all the active cron processes manually"
		if [ $BATCH -eq 1 ]; then
			return
		fi
		ask "Continue with the update?" "Y/N"
		if [ "$_RET" = "n" ]; then
			info "Update cancelled by user"
			exit $RETCODE_OK
		else
			return
		fi
	fi

	# get vte full path and escape it for regexp
	local fullvtepath=$(readlink -n -f "$VTEDIR" | sed 's#\/#\\/#g')

	# suspend the cron
	sed -i "s/^[^#].*${fullvtepath}.*\(cron\|import\|erpconnector\).*/### VTEUPDATE ### \0/ig" /etc/crontab

	debug "Cron processes disabled"

	if [ $SKIPCRON -eq 1 ]; then
		return
	fi

	# wait for end
	local counter=120
	local cronline=""
	info "Waiting 2 minutes for cron processes to finish..."
	while [ $counter -gt 0 ]; do
		cronline=$(ps aux | grep "$fullvtepath" | grep -v -F grep)
		if [ -z "$cronline" ]; then
			break;
		fi
		echo -n "."
		counter=$(($counter - 1))
		sleep 1
	done
	echo ""

	if [ -n "$cronline" ]; then
		warning "Some cron processes are taking a long time to terminate. You should terminate them manually"
		if [ $BATCH -eq 0 ]; then
			ask "Continue with the update?" "Y/N"
			if [ "$_RET" = "n" ]; then
				info "Update cancelled by user"
				exit $RETCODE_OK
			fi
		fi
	fi

}

create_selfupdate () {

	# create the file with the old version
	echo '<?php $vteversion_preupdate = "'"$VTEREVISION"'"; ' > "vteversion_old.php"

	# create the update file
	cat << 'ENDPHP' > "$SELFUPDATE"
<?php
// check if calling from command line
if (PHP_SAPI != 'cli' || empty($_SERVER['argv']) || $_SERVER['argc'] < 3) die('ERROR: Not Permitted');

$vte_self_update = true;

require('config.inc.php');

// enable die on error
$dbconfig['db_dieOnError'] = true;

require('vteversion_old.php');
require_once('include/utils/utils.php');
require_once('modules/Update/Update.php');

$pstart = intval($_SERVER['argv'][1]);
$pend = intval($_SERVER['argv'][2]);

if ($pstart <= 0 || $pend <= 0 || $pstart >= $pend || $vteversion_preupdate != $pstart || $enterprise_current_build != $pend) {
	echo "ERROR: Wrong version ($pstart -> $pend, VTE: $vteversion_preupdate)\n";
	exit(1);
}

global $currentModule, $current_user, $current_language;
global $log, $adb, $table_prefix;

if (empty($table_prefix)) $table_prefix = 'vtiger';


$current_language = 'it_it';
$currentModule = 'Update';

$current_user = CRMEntity::getInstance('Users');
$current_user->id = 1;

$_REQUEST['module'] = 'Update';

if (property_exists('Update', 'logPrefix')) {
	Update::$logPrefix = 'VTEUPDATER##';
}
$focus = new $currentModule('server','srv_user','srv_pwd',$pstart);
$focus->to_version = $pend;

// raise PHP limits
ini_set('memory_limit','1024M');
set_time_limit(0);

// launch update
$r = $focus->update_changes();
if ($r === false) {
	echo "ERROR: update failed\n";
	exit(1);
}

// remove open sessions
if (Vtiger_Utils::CheckTable("{$table_prefix}_userauthtoken")) {
	$adb->query("truncate table {$table_prefix}_userauthtoken");
}
if (Vtiger_Utils::CheckTable("{$table_prefix}_ws_userauthtoken")) {
	$adb->query("truncate table {$table_prefix}_ws_userauthtoken");
}

// remove cache files
exec("rm -f smartoptimizer/cache/*gz", $output, $ret);
exec("rm -f Smarty/templates_c/*php", $output, $ret);

echo "\nUPDATE FINISHED\n";

ENDPHP
}

# apply a fix for the update class wich lower the max execution time if it was already 0
fix_update_class () {
	debug "Fixing Update.php file..."

	local SRCFILE="$VTEDIR"modules/Update/Update.php
	local DESTFILE="$TEMPDIR"Update.php.bak

	# clean and make a backup copy
	rm -f "$DESTFILE" 2>/dev/null
	cp -f "$SRCFILE" "$DESTFILE"
	if [ ! -r "$DESTFILE" ]; then
		warning "Unable to read Update.php file, the update can continue anyway"
		return
	fi

	# patch the file
	sed -i 's/^\s*set_time_limit([0-9]\+);\s*$/$met = ini_get("max_execution_time"); if ($met > 0 \&\& $met < 600) set_time_limit(600);/g' "$SRCFILE"

	# check if there was any change
	_RET=1
	cmp -s "$SRCFILE" "$DESTFILE" || _RET=0
	if [ $_RET -eq 1 ]; then
		# equals, remove the bak
		debug "Update.php is already patched"
		rm -f "$DESTFILE" 2>/dev/null
	fi
}

# revert the previous fix
clean_fix_update_class () {
	debug "Restoring Update.php file..."

	local SRCFILE="$VTEDIR"modules/Update/Update.php
	local DESTFILE="$TEMPDIR"Update.php.bak

	if [ -r "$DESTFILE" ]; then
		cp -f "$DESTFILE" "$SRCFILE" && rm -f "$DESTFILE" 2>/dev/null
	fi
}

remove_files () {
	if [ $SKIPFILEREMOVE -eq 1 -o ! -s "$DELFILE" ]; then
		debug "Skipped files removal phase"
	fi
	
	info "Removing obsolete files..."
	
	local now=$(date +"%Y%m%d-%H%M%S")
	local BAKNAME="$now""_removed.tgz"
	BACKUPDELFILE="$BACKUPDIR""$BAKNAME"
	
	local REMFILE
	local HASH
	local VTEHASH
	local NDELFILES=0
	local NDELDIRS=0

	if [ -s "$DELFILE_DIR""files.txt" ]; then
		
		debug "Creating backup of removed files..."
		cut -f 1 -d $'\t' "$DELFILE_DIR""files.txt" | tar --ignore-failed-read --exclude "vte_updater" --exclude "storage" --exclude "vteUpdater.sh" -czf "$BACKUPDELFILE" -T- 2>/dev/null
	
		while read -r row; do
			REMFILE="$VTEDIR"${row%%$'\t'*}
			HASH=${row##*$'\t'}
			if [ -f "$REMFILE" ]; then
				VTEHASH=$(sha1sum "$REMFILE" | cut -f 1 -d " ");
				if [ -n "$VTEHASH" -a "$VTEHASH" = "$HASH" ]; then
					rm -f "$REMFILE" && {
						debug "File $REMFILE removed"
						NDELFILES=$((NDELFILES+1))
					}
				else 
					warning "The file $REMFILE might have some custom code, left untouched"
				fi
			fi
		done < "$DELFILE_DIR""files.txt"
	fi
	
	if [ -s "$DELFILE_DIR""directories.txt" ]; then
		while read -r row; do
			if [ -d "$row" ]; then
				if rmdir "$row" 2>/dev/null; then
					debug "Directory $row removed"
					NDELDIRS=$((NDELDIRS+1))
				else
					warning "Directory $row is not empty, left untouched"
				fi
			fi
		done < "$DELFILE_DIR""directories.txt"
	fi
	
	info "Removed $NDELFILES files and $NDELDIRS directories"
	
}

do_update () {
	info "Starting update..."

	if [ ! -d "$PACKAGE_DIR" ]; then
		error "$PACKAGE_DIR directory not found"
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi
	
	create_selfupdate
	if [ ! -s "$SELFUPDATE" ]; then
		error "Update script $SELFUPDATE was not created succesfully"
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi
	
	# check versions 
	if [ $VTEREVISION -ge $DESTREVISION ]; then
		error "The destination revision is lower or equal than the installed one"
		restore_cron_and_vte
		exit $RETCODE_FAIL
	fi
	
	# after this point, vte will be corrupted if interrupted
	
	# copy files
	cp -rf "$PACKAGE_DIR"* "$VTEDIR"
	
	if [ -n "$PREPATCHDIR" ]; then
		cp -rf "$PREPATCHDIR"/* "$VTEDIR" && {
			info "Files from $PREPATCHDIR copied."
		} || {
			warning "Error while copying files from $PREPATCHDIR. Continuing with the update..."
		}
	fi
	
	# fis permissions
	fix_permissions "$VTEDIR"

	fix_update_class
	
	info "Files copied. Executing schema update..."
	
	local now=$(date +"%Y%m%d-%H%M%S")
	local BAKNAME="$now""_vteupdate.log"
	local NOTESNAME="$now""_update_notes.log"
	UPDATELOG="$WORKDIR""$BAKNAME"
	NOTESLOG="$WORKDIR""$NOTESNAME"

	# execute update
	php -f "$VTEDIR""$SELFUPDATE" "$VTEREVISION" "$DESTREVISION" > "$UPDATELOG" 2>&1
	_RET=$?
	debug "PHP Update script terminated with status $_RET"
	if [ $_RET -gt 0 ]; then
		clean_fix_update_class
		error "There were errors during the update, please check log file $UPDATELOG"
		if [ $BATCH -eq 1 ]; then
			cat "$UPDATELOG"
		else
			ask "Do you want do display the log here?" "Y/N"
			if [ "$_RET" = "y" ]; then
				cat "$UPDATELOG"
			fi
		fi
		do_rollback
		return
	fi
	
	# check logfile
	grep -q -E -i "warning|error|exception|fatal" "$UPDATELOG" && {
		clean_fix_update_class
		error "There were errors during the update, please check log file $UPDATELOG"
		if [ $BATCH -eq 1 ]; then
			cat "$UPDATELOG"
		else
			ask "Do you want do display the log here?" "Y/N"
			if [ "$_RET" = "y" ]; then
				cat "$UPDATELOG"
			fi
		fi
		do_rollback
		return
	}

	# check for the signature of update end
	grep -q "^UPDATE FINISHED\$" "$UPDATELOG" || {
		clean_fix_update_class
		error "There were errors during the update, please check log file $UPDATELOG"
		if [ $BATCH -eq 1 ]; then
			cat "$UPDATELOG"
		else
			ask "Do you want do display the log here?" "Y/N"
			if [ "$_RET" = "y" ]; then
				cat "$UPDATELOG"
			fi
		fi
		do_rollback
		return
	}
	
	clean_fix_update_class
	
	remove_files
	
	# check for update notes
	grep -F "VTEUPDATER##" "$UPDATELOG" > "$NOTESLOG"
	if [ -s "$NOTESLOG" ]; then
		# clean it up!
		sed -i "s/^.*VTEUPDATER##//g" "$NOTESLOG"
		info "Update notes:";
		cat "$NOTESLOG"
	fi
	
	if [ -n "$POSTPATCHDIR" ]; then
		cp -rf "$POSTPATCHDIR"/* "$VTEDIR" && {
			info "Files from $POSTPATCHDIR copied."
		} || {
			warning "Error while copying files from $POSTPATCHDIR. Please copy them manually."
		}
	fi
	
	# change permission again
	fix_permissions "$VTEDIR"
	
	info "Update successful!"
}

do_rollback () {
	
	if [ $BATCH -eq 0 ]; then
		ask "Do you want to restore the VTE to its original status?" "Y/N"
		if [ "$_RET" = 'n' ]; then
			warning "Rollback skipped by user"
			
			# ask to apply post patches
			if [ -n "$POSTPATCHDIR" ]; then
				ask "Do you want to apply post-patches anyway?" "Y/N"
				if [ "$_RET" = 'y' ]; then
					cp -rf "$POSTPATCHDIR"/* "$VTEDIR" && {
						info "Files from $POSTPATCHDIR copied."
					} || {
						warning "Error while copying files from $POSTPATCHDIR. Please copy them manually."
					}
					fix_permissions "$VTEDIR"
				fi
			fi
			
			return
		fi
	fi
	
	check_command mysql
	
	if [ ! -r "$BACKUPFILE" ]; then
		error "Backup file $BACKUPFILE not found. This is very bad!"
		warning "VTE left in inconsistent state"
		exit $RETCODE_FAIL
	fi
	
	info "Starting rollback..."
	
	# restore files
	if [ $COMP_TAR_VCS -eq 1 ]; then
		tar --exclude-vcs --exclude "vte_updater/*" --exclude "storage/*" --exclude "vteUpdater.sh" -xzf "$BACKUPFILE" -C "$VTEDIR"
	else
		tar --exclude ".git" --exclude ".svn" --exclude "vte_updater/*" --exclude "storage/*" --exclude "vteUpdater.sh" -xzf "$BACKUPFILE" -C "$VTEDIR"
	fi
	if [ $? -gt 0 ]; then
		error "Error extracting $1"
		warning "VTE left in inconsistent state"
		exit $RETCODE_FAIL
	fi
	# check if double version files, keep the old one
	if [ -f "$VTEDIR""vtigerversion.php" -a -f "$VTEDIR""vteversion.php" ]; then
		rm "$VTEDIR""vteversion.php"
	fi
	debug "Backup of files restored"
	
	# restore db
	if [ ! -r "$BACKUPDBFILE" ]; then
		warning "Backup database file $BACKUPDBFILE not found. If you made a manual backup, please restore it now."
		warning "VTE left in inconsistent state"
		exit $RETCODE_FAIL
	fi
	
	# check if I can drop and then re-create, otherwise new tables might cause problems
	local CANDROP=0
	local TESTDBNAME="vteupd_test_"$(date +"%Y%m%d")
	
	# get old db charset
	local DBCHARSET=$(mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -s -N -e "SELECT default_character_set_name FROM information_schema.SCHEMATA WHERE schema_name = '$DBNAME'")
	if [ -z "$DBCHARSET" ]; then
		DBCHARSET="utf8"
	fi
	
	# silently remove test db
	mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -e "DROP DATABASE IF EXISTS $TESTDBNAME;"
	# try to create
	mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -e "CREATE DATABASE $TESTDBNAME CHARACTER SET $DBCHARSET;" && \
		mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -e "DROP DATABASE $TESTDBNAME;" && \
		CANDROP=1
	
	# do the real drop+create
	if [ $CANDROP -eq 1 ]; then
		mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -e "DROP DATABASE $DBNAME"
		if [ $? -gt 0 ]; then
			warning "Unable to drop database, the rollback will continue, but there might be problems with the next update."
		else
			mysql -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" -e "CREATE DATABASE $DBNAME CHARACTER SET $DBCHARSET;"
			if [ $? -gt 0 ]; then
				error "Unable to create the database, please restore it manually."
				exit $RETCODE_FAIL
			fi
			debug "Database recreated correctly"
		fi
	else
		warning "The current mysql user cannot recreate the database. The rollback will continue, but there might be problems with the next update."
	fi
	
	# ok, restore it!
	zcat "$BACKUPDBFILE" | mysql --default-character-set=utf8 -h "$DBHOST" -P "$DBPORT" -u "$DBUSER" --password="$DBPWD" "$DBNAME"
	if [ $? -gt 0 ]; then
		error "Error restoring database"
		warning "VTE left in inconsistent state"
		exit $RETCODE_FAIL
	fi
	debug "Backup of database restored"
	
	info "Rollback completed"
	ROLLBACKOK=1
}

vtews_call () {
	# $1 = wsname
	# $2...$n = parameters in form $2 = name, $3 = value, $3 = name2, $4 = value ....
	# return 0 on error, the json encoded result otherwise
	
	# prepare data
	local WSNAME="$1"
	local data=""
	
	shift
	while [ $# -gt 0 ]; do
		
		local PNAME="$1"
		shift
		local PVAL="$1"
		shift
		
		[ -n "$data" ] && data="$data""&"
		data="$data""${PNAME}="$(php -r 'echo urlencode($argv[1]);' "$PVAL")
	done
	
	debug "Calling webservice on ${CRMVILLAGE_VTE}: $WSNAME"
	
	local OUTPUT=$(wget --quiet --timeout 20 "${CRMVILLAGE_VTE}webservice.php?operation=$WSNAME" --post-data="$data" -O - )
	
	local success=$(php -r 'if ($d = json_decode($argv[1], true)) { echo ($d["success"] ? 1 : 0); } else echo 0; ' "$OUTPUT")
	if [ $success -eq 1 ]; then
		local result=$(php -r 'if ($d = json_decode($argv[1], true)) echo json_encode($d["result"]);' "$OUTPUT")
		_RET="$result"
	else
		local message=$(php -r 'if ($d = json_decode($argv[1], true)) { echo $d["error"]["message"]; } else echo "Invalid response"; ' "$OUTPUT")
		warning "Error during request: $message"
		_RET=0
	fi	
}

update_ws_call () {
	# $1 = wsname
	# $2...$n = parameters in form $2 = name, $3 = value, $3 = name2, $4 = value ....
	# return 
	
	# prepare data
	local WSNAME="$1"
	local data=""
	
	shift
	while [ $# -gt 0 ]; do
		
		local PNAME="$1"
		shift
		local PVAL="$1"
		shift
		
		[ -n "$data" ] && data="$data""&"
		data="$data""${PNAME}="$(php -r 'echo urlencode($argv[1]);' "$PVAL")
	done
	
	debug "Calling webservice on ${UPDATE_SERVER}: $WSNAME"
	
	local OUTPUT=$(wget --quiet --timeout 20 "${UPDATE_SERVER}ws.php?wsname=$WSNAME" --post-data="$data" -O - )

	local success=$(php -r 'if ($d = json_decode($argv[1], true)) { echo ($d["success"] ? 1 : 0); } else echo 0; ' "$OUTPUT")
	if [ $success -eq 1 ]; then
		local result=$(php -r 'if ($d = json_decode($argv[1], true)) echo json_encode($d["result"]);' "$OUTPUT")
		_RET="$result"
	else
		local message=$(php -r 'if ($d = json_decode($argv[1], true)) { echo $d["error"]; } else echo "Invalid response"; ' "$OUTPUT")
		warning "Error during request: $message"
		debug "Server responded with $OUTPUT"
		_RET=0
	fi	
}

get_user_accesskey () {
	
	if [ -n "$ACCESSKEY" -a -n "$USERNAME" ]; then
		debug "Using provided username $USERNAME and accesskey"
		HASHEDKEY=$(echo -n "$ACCESSKEY" | md5sum | cut -f 1 -d " ")
		return
	fi
	
	if [ $BATCH -eq 1 ]; then
		error "In batch mode you have to provide username and accesskey from the commandline"
		exit $RETCODE_FAIL
	fi
	
	local COUNTER=1
	while true; do
	
		ask "Please type your username" "" "0"
		USERNAME=$_RET
		
		ask_pwd "Password"
		local PASSWORD=$_RET
	
		# validate on server
		info "Validating credentials..."
		vtews_call "login_pwd" "username" "${USERNAME}" "password" "${PASSWORD}"
		if [ -z "$_RET" ]; then
			error "Error validating credentials"
			exit $RETCODE_FAIL
		elif [ "$_RET" = "0" ]; then
			if [ $COUNTER -ge 3 ]; then
				error "Invalid credentials. Too many attempts, exiting."
				exit $RETCODE_FAIL
			fi
			COUNTER=$(($COUNTER+1))
			error "Invalid credentials, try again"
			continue
		fi
		ACCESSKEY=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d[0];' "$_RET")
		break
	done
	
	if [ -z "$ACCESSKEY" ]; then
		error "Invalid accesskey"
		exit $RETCODE_FAIL
	fi
	
	HASHEDKEY=$(echo -n "$ACCESSKEY" | md5sum | cut -f 1 -d " ")

	debug "Accesskey succesfully retrieved"
}

get_safe_dest_revision () {
	local VTEREV=$1
	
	# read ranges from server
	update_ws_call "get_safe_update_ranges"
	if [ -z "$_RET" -o "$_RET" = "0" ]; then
		error "Unable to communicate with the update server"
		exit $RETCODE_FAIL
	fi
	REVRANGES=$(php -r '$r = array(); if ($d = json_decode($argv[1], true)) { array_walk_recursive($d, function($a) use (&$r) { $r[] = $a; }); echo implode(" ", $r); }' "$_RET")
	read -r -a REVRANGES <<< "$REVRANGES"
	
	local REVCOUNT=${#REVRANGES[@]}
	local REVMAXIDX=$(( $REVCOUNT / 3 - 1 ))
	
	_RET=""
	for i in $(seq 0 $REVMAXIDX); do 
		if [ $VTEREV -ge ${REVRANGES[$i*3]} -a $VTEREV -le ${REVRANGES[$i*3+1]} ]; then
			_RET=${REVRANGES[$i*3+2]}
			break
		fi
	done
}

is_safe_revision () {
	local CHECKREV=$1
	local REVCOUNT=${#REVRANGES[@]}
	local REVMAXIDX=$(( $REVCOUNT / 3 - 1 ))
	
	_RET=0
	for i in $(seq 0 $REVMAXIDX); do 
		if [ $CHECKREV -eq ${REVRANGES[$i*3+2]} ]; then
			_RET=1
			break
		fi
	done
}

get_dest_revision () {
	
	get_safe_dest_revision $VTEREVISION
	local SAFEREV=$_RET
	
	# check if specified from command line
	if [ -n "$DESTREVISION" -a $DESTREVISION -gt 0 ]; then
		if [ $DESTREVISION -le $VTEREVISION ]; then
			error "The destination revision is lower or equal than the installad version"
			exit $RETCODE_FAIL
		fi
		
		if [ -n "$SAFEREV" -a "$SAFEREV" != "$DESTREVISION" ]; then
			is_safe_revision $DESTREVISION
			if [ "$_RET" = "0" ]; then
				if [ $BATCH -eq 1 ]; then
					warning "You are updating to an unsupported revision (the safest one would be $SAFEREV). Continuing anyway since we are in batch mode."
					_RET='y'
				else 
					ask "You are updating to an unsupported revision (the safest one would be $SAFEREV). Do you want to proceed anyway?" "Y/N"
				fi
			else
				if [ $BATCH -eq 1 ]; then
					warning "You are updating to an unstable revision and the update can fail. Continuing anyway since we are in batch mode."
					_RET='y'
				else 
					ask "Updating to the chosen revision is risky and the update can fail (the safest one would be $SAFEREV). Do you want to proceed anyway?" "Y/N"
				fi
			fi
			
			if [ $_RET = 'n' ]; then
				info "Update cancelled by user"
				exit $RETCODE_OK
			fi
		fi
		
		# php version check (hardcoded for now, might be dynamic in the future)
		if [ $DESTREVISION -ge 1819 ]; then
			local PHPVERSION=$(php -r "echo phpversion();")
			local MINPHPVERSION="7.0"
			version_lt "$PHPVERSION" "$MINPHPVERSION" && {
				error "The destination revision supports only PHP >= $MINPHPVERSION. Please update the system before updating."
				exit $RETCODE_FAIL
			}
		fi
		
		return
	fi
	
	info "Checking for latest available version..."
	
	update_ws_call "get_latest_revision"
	if [ -z "$_RET" -o "$_RET" = "0" ]; then
		error "Unable to communicate with the update server"
		exit $RETCODE_FAIL
	fi
	
	DESTREVISION=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d;' "$_RET")
	debug "Latest version available: $DESTREVISION"
	
	if [ $DESTREVISION -le $VTEREVISION ]; then
		info "No new versions available"
		exit $RETCODE_OK
	else
		if [ -z "$SAFEREV" ]; then
			if [ $BATCH -eq 1 ]; then
				warning "Updating to revision $DESTREVISION, not yet released and not officially supported."
			else
				ask "You can update to revision $DESTREVISION, which is not yet released and not officially supported. Do you want to proceed?" "Y/N"
			fi
		else
			if [ $SAFEREV -lt $DESTREVISION ]; then
				debug "An even newer version is available, but the safest revision ($SAFEREV) is being used"
			fi
			DESTREVISION=$SAFEREV
			if [ $BATCH -eq 0 ]; then
				ask "You can update to revision $DESTREVISION. Do you want to proceed?" "Y/N"
			fi
		fi
		
		if [ $_RET = 'n' ]; then
			info "Update cancelled by user"
			exit $RETCODE_OK
		fi
	fi
	
	# php version check (hardcoded for now, might be dynamic in the future)
	if [ $DESTREVISION -ge 1819 ]; then
		local PHPVERSION=$(php -r "echo phpversion();")
		local MINPHPVERSION="7.0"
		version_lt "$PHPVERSION" "$MINPHPVERSION" && {
			error "The destination revision supports only PHP >= $MINPHPVERSION. Please update the system before updating."
			exit $RETCODE_FAIL
		}
	fi
}

fetch_package () {
	
	# check if we have the packages from command line
	if [ -s "$PACKAGEFILE" -a \( -s "$SRCFILE" -o $SKIPFILECHECK -eq 1 \) ]; then
		debug "Using update and source file from command line"
		return
	fi
	
	# first check if package is already here
	local UPDFILELOC="$PACKAGESDIR""vte${VTEREVISION}-${DESTREVISION}.tgz"
	local SRCFILELOC="$PACKAGESDIR""vte${VTEREVISION}-${DESTREVISION}src.tgz"
	local DELFILELOC="$PACKAGESDIR""vte${VTEREVISION}-${DESTREVISION}del.tgz"
	
	if [ -s "$UPDFILELOC" -a -s "$SRCFILELOC" ]; then
		PACKAGEFILE="$UPDFILELOC"
		SRCFILE="$SRCFILELOC"
		info "Found needed packages in local cache, using them."
		debug "Using packages $PACKAGEFILE and $SRCFILE"
		if [ -s "$DELFILELOC" ]; then
			DELFILE="$DELFILELOC"
			debug "Using also deleted files list $DELFILE"
		fi
		return
	fi
	
	if [ $BATCH -eq 1 ]; then
		error "In batch mode you should manually provide the update packages or be sure they are in the local cache"
		exit $RETCODE_FAIL
	fi
	
	# check if the package is ready
	update_ws_call "is_package_available" "start_revision" "$VTEREVISION" "dest_revision" "$DESTREVISION"
	if [ -z "$_RET" -o "$_RET" = "0" ]; then
		error "Unable to communicate with the update server"
		exit $RETCODE_FAIL
	fi

	local AVAIL=$(php -r 'if ($d = json_decode($argv[1], true)) echo ($d ? 1 : 0); else echo 0;' "$_RET")
	
	if [ $AVAIL -eq 0 ]; then
		# no package available, request it and exit
		# for community, no requests are possible
		if [ $ISCOMMUNITY -eq 1 ]; then
			info "The update package is not available, try again later"
			exit $RETCODE_OK
		fi
		
		info "The update package is not available at the moment, but you can submit a request and you'll be informed as soon as it will be ready"
		
		while true; do
			ask "To continue, type the email address you want to be notified"
			local EMAIL="$_RET"
			if [[ "$EMAIL" =~ [a-zA-Z0-9_.+%-]+@[a-zA-Z0-9_.-]+\.[a-zA-Z]{2,5} ]]; then
				break
			else
				error "The address provided is invalid"
			fi
		done
		
		get_user_accesskey
		
		info "Sending request..."
		update_ws_call "request_package" \
			"username" "$USERNAME" "hashedkey" "$HASHEDKEY" \
			"start_revision" "$VTEREVISION" "dest_revision" "$DESTREVISION" \
			"email" "$EMAIL" "vte_url" "$VTEURL"
		
		if [ -z "$_RET" -o "$_RET" = "0" ]; then
			error "Unable to communicate with the update server"
			exit $RETCODE_FAIL
		else
			info "Thank you! The update package will be generated shortly. You'll receive an email when it will be ready"
		fi
		exit $RETCODE_OK
	else
		# download package
		
		if [ $ISCOMMUNITY -eq 1 ]; then
			info "Downloading package..."
			
			update_ws_call "request_download" \
				"start_revision" "$VTEREVISION" "dest_revision" "$DESTREVISION" \
				"vte_url" "$VTEURL"
		else
			get_user_accesskey
		
			info "Downloading package..."
		
			update_ws_call "request_download" \
				"username" "$USERNAME" "hashedkey" "$HASHEDKEY" \
				"start_revision" "$VTEREVISION" "dest_revision" "$DESTREVISION" \
				"vte_url" "$VTEURL"
		fi
		
		if [ -z "$_RET" -o "$_RET" = "0" ]; then
			error "Unable to communicate with the update server"
			exit $RETCODE_FAIL
		fi
		
		local REMOTESRC=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d["src"];' "$_RET")
		local REMOTEUPD=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d["upd"];' "$_RET")
		local REMOTEDEL=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d["del"];' "$_RET")
		
		debug "Remote packages are $REMOTEUPD and $REMOTESRC"
		if [ -n "$REMOTEDEL" ]; then
			debug "Remote deleted files list is $REMOTEDEL"
		fi
		
		if [ -z "$REMOTESRC" -o -z "$REMOTEUPD" ]; then
			error "Unable to communicate with the update server"
			exit $RETCODE_FAIL
		fi
		
		#local OUTFILESRC="$PACKAGESDIR"$(basename "${REMOTESRC/_[a-zA-Z0-9]*./.}" )
		#local OUTFILEUPD="$PACKAGESDIR"$(basename "${REMOTEUPD/_[a-zA-Z0-9]*./.}" )
		
		# TODO use curl if wget is not available

		debug "Packages download started..."
		
		if [[ "$REMOTESRC" =~ ^http ]]; then
			# absolute url
			wget -nv --progress=bar:force "$REMOTESRC" -O "$SRCFILELOC"
		else
			wget -nv --progress=bar:force "$UPDATE_SERVER""$REMOTESRC" -O "$SRCFILELOC"
		fi
		if [ $? -gt 0 ]; then
			rm -f "$SRCFILELOC" 2>/dev/null
			error "Error during download"
			exit $RETCODE_FAIL
		fi
		
		if [[ "$REMOTEUPD" =~ ^http ]]; then
			wget -nv --progress=bar:force "$REMOTEUPD" -O "$UPDFILELOC"
		else
			wget -nv --progress=bar:force "$UPDATE_SERVER""$REMOTEUPD" -O "$UPDFILELOC"
		fi
		if [ $? -gt 0 ]; then
			rm -f "$UPDFILELOC" "$SRCFILELOC" 2>/dev/null
			error "Error during download"
			exit $RETCODE_FAIL
		fi
		
		if [ -n "$REMOTEDEL" ]; then
			if [[ "$REMOTEDEL" =~ ^http ]]; then
				wget -nv --progress=bar:force "$REMOTEDEL" -O "$DELFILELOC"
			else
				wget -nv --progress=bar:force "$UPDATE_SERVER""$REMOTEDEL" -O "$DELFILELOC"
			fi
			if [ $? -gt 0 ]; then
				rm -f "$DELFILELOC" 2>/dev/null
				warning "Unable to download deleted files list. This step will be skipped."
			fi
		fi
		
		if [ -s "$UPDFILELOC" -a -s "$SRCFILELOC" ]; then
			PACKAGEFILE="$UPDFILELOC"
			SRCFILE="$SRCFILELOC"
			if [ -s "$DELFILELOC" ]; then
				DELFILE="$DELFILELOC"
			fi
			info "Packages downloaded succesfully"
		else
			error "Packages were not downloaded correctly"
			exit $RETCODE_FAIL
		fi
		
	fi
	
}

check_only_backup () {
	if [ $ONLYBACKUP -eq 1 ]; then
		create_vte_backup
		create_db_backup
		
		info "Backup completed. Backup files are $BACKUPFILE and $BACKUPDBFILE"
		exit $RETCODE_OK
	fi
}

check_only_enable () {
	if [ $ENABLEVTE -eq 1 ]; then
		info "Enabling VTE..."
		enable_vte
		exit $RETCODE_OK
	elif [ $ENABLEVTE -eq 2 ]; then
		info "Disabling VTE..."
		disable_vte
		exit $RETCODE_OK
	fi
}

check_only_rollback () {
	if [ -n "$ONLYROLLBACK" -a "$ONLYROLLBACK" -gt 0 ]; then
		if [ $ONLYROLLBACK -ge $VTEREVISION ]; then
			error "You can't rollback to a revision greater than the actual VTE"
			exit $RETCODE_FAIL
		fi
		# look for update files
		local SEARCHDIR="$VTEDIR"vte_updater/"$ONLYROLLBACK"/backup/
		if [ ! -d "$SEARCHDIR" ]; then
			error "No backup found for the specified revision"
			exit $RETCODE_FAIL
		fi
		# look for files
		local datepart
		local filebak
		local dbbak
		for f in "$SEARCHDIR"*_files.tgz; do
			filebak="$f"
			datepart=${f%%-*}
			# look for db file
			for dbf in "${datepart}-"*_db.sql.gz; do
				dbbak="$dbf"
				break
			done
			if [ -s "$filebak" -a -s "$dbbak" ]; then
				break
			fi
		done
		if [ ! -s "$filebak" -o ! -s "$dbbak" ]; then
			error "No backup found for the specified revision"
			exit $RETCODE_FAIL
		fi
		BACKUPFILE="$filebak"
		BACKUPDBFILE="$dbbak"
		
		debug "Using backup files $BACKUPFILE and $BACKUPDBFILE for rollback"

		# extract date and time
		local bakdate=$(basename "$BACKUPFILE")
		bakdate=${bakdate:0:4}"-"${bakdate:4:2}"-"${bakdate:6:2}
		local baktime=$(basename "$BACKUPFILE")
		baktime=${baktime:9:2}":"${baktime:11:2}
		local bakdbtime=$(basename "$BACKUPDBFILE")
		bakdbtime=${bakdbtime:9:2}":"${bakdbtime:11:2}
		
		info "Found backup in date $bakdate (Time of backup: Files $baktime, Database $bakdbtime)"
		
		get_db_config
	
		if [ "$DBTYPE" != "mysql" -a "$DBTYPE" != "mysqli" ]; then
			error "The database type ($DBTYPE) is not supported for automatic rollback."
			exit $RETCODE_FAIL
		fi
			
		do_rollback
		enable_vte
		
		exit $RETCODE_OK
	fi
}

check_only_delete () {
	if [ -n "$ONLYDELETE" -a "$ONLYDELETE" -gt 0 ]; then
	
		if [ $BATCH -eq 1 ]; then
			error "Option --only-delete cannot be used in batch mode"
			exit $RETCODE_FAIL
		fi
	
		if [ -z "$DELFILE" ]; then
			local STARTREV
			while true; do
				ask "From which revision was this VTE updated?"
				if [ "$_RET" -ge 1000 -a "$_RET" -lt "$VTEREVISION" ]; then
					STARTREV="$_RET"
					break
				else
					warning "Invalid revision specified"
				fi
			done

			# switch revisions
			local OLDREVISION="$VTEREVISION"
			DESTREVISION="$VTEREVISION"
			VTEREVISION="$STARTREV"

			fetch_package
			VTEREVISION="$OLDREVISION"
			DESTREVISION=""
		fi
		
		if [ ! -f "$DELFILE" ]; then
			error "Unable to retrieve a valid deleted files package"
			exit $RETCODE_FAIL
		fi
		
		unpack_del $DELFILE
		
		remove_files
		
		exit $RETCODE_OK
	fi
}

check_use_package () {
	if [ -n "$USELOCALPKG" ]; then
		if [ ! -r "$USELOCALPKG" -o ! -s "$USELOCALPKG" ]; then
			error "The specified update file $USELOCALPKG was not found"
			exit $RETCODE_FAIL
		fi
		if [ $SKIPFILECHECK -eq 0 -a -z "$USELOCALSRCPKG" ]; then
			error "You must also provide the source package with the --src-package option"
			exit $RETCODE_FAIL
		elif [ $SKIPFILECHECK -eq 0 -a ! -s "$USELOCALSRCPKG" ]; then
			error "The specified source file $USELOCALSRCPKG was not found"
			exit $RETCODE_FAIL
		fi
		# now check for the internal revision
		local TMPFILE="$TEMPDIR"tmp_vteversion.php
		tar --wildcards -Oxf "$USELOCALPKG" "*/vt*version.php" > "$TMPFILE"
		if [ $? -gt 0 -o ! -s "$TMPFILE" ]; then
			error "The provided package is not a valid update package"
			rm -f "$TMPFILE"
			exit $RETCODE_FAIL
		fi
		extract_php_global_var "$TMPFILE" "enterprise_current_build"
		if [ -z "$_RET" ]; then
			error "The provided package is not a valid update package"
			rm -f "$TMPFILE"
			exit $RETCODE_FAIL
		fi
		DESTREVISION=$_RET
		rm -f "$TMPFILE"
		
		PACKAGEFILE="$USELOCALPKG"
		SRCFILE="$USELOCALSRCPKG"
		debug "Using provided package and source files $USELOCALPKG, $USELOCALSRCPKG to update VTE to revision $DESTREVISION"
	elif [ -n "$USELOCALSRCPKG" ]; then
		warning "Option --src-package without --upd-package has no effect"
	fi
	
	if [ -n "$USELOCALDELPKG" ]; then
		if [ ! -r "$USELOCALDELPKG" -o ! -s "$USELOCALDELPKG" ]; then
			error "The specified deleted files list $USELOCALDELPKG was not found"
			exit $RETCODE_FAIL
		fi
		DELFILE="$USELOCALDELPKG"
	fi
}

self_update () {

	if [ $SKIPUPGRADE -eq 1 ]; then
		info "Self update skipped as specified on command line"
		return
	fi
	
	if [ $FORCEUPGRADE -eq 1 -a $BATCH -eq 1 ]; then
		error "You cannot specify --force-upgrade and --batch at the same time"
		exit $RETCODE_FAIL
	fi
	
	if [ $BATCH -eq 1 ]; then
		info "Self update skipped in batch mode"
		return
	fi

	local LASTUPDATEFILE="$WORKDIR""last_update_check"
	
	if [ $FORCEUPGRADE -eq 0 -a -s "$LASTUPDATEFILE" ]; then
		# check the content
		local NOW=$(date +%Y%m%d)
		local LASTCHECK=$(cat "$LASTUPDATEFILE")
		if [ -n "$NOW" -a -n "$LASTCHECK" ]; then
			# do the comparison
			if [ "$NOW" = "$LASTCHECK" ]; then
				debug "Last check for updates was today, skipped"
				return
			fi
		fi
	fi

	# check for new versions
	info "Checking for upgrades of this script..."
	
	update_ws_call "get_latest_script_version"
	if [ -z "$_RET" -o "$_RET" = "0" ]; then
		warning "Unable to communicate with the update server, self update skipped"
		return
	fi

	# save last update time to file
	date +%Y%m%d > "$LASTUPDATEFILE"

	local NEWVERSION=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d;' "$_RET")
	local COMP=$(php -r 'echo version_compare($argv[1], $argv[2]);' "$VERSION" "$NEWVERSION")
	
	if [ "$COMP" = "-1" ]; then
		info "Upgrading script..."
		update_ws_call "request_script_download"
		if [ -z "$_RET" -o "$_RET" = "0" ]; then
			warning "Unable to communicate with the update server, self update skipped"
			return
		fi
		local URL=$(php -r 'if ($d = json_decode($argv[1], true)) echo $d["url"];' "$_RET")
		
		debug "Remote script is $URL"
		if [ -z "$URL" ]; then
			warning "Unable to communicate with the update server, self update skipped"
			return
		fi		
		
		debug "Downloading script..."
		local SCRIPTTMP="$WORKDIR""vteUpdater-${NEWVERSION}_temp.tgz"
		wget -nv -q "$UPDATE_SERVER""$URL" -O "$SCRIPTTMP"

		if [ $? -gt 0 -o ! -s "$SCRIPTTMP" ]; then
			rm -f "$SCRIPTTMP" 2>/dev/null
			warning "Error during download, self update skipped"
			return
		fi
		
		debug "Upgrade downloaded successfully"

		# now decompress it
		local NEWSCRIPT="${WORKDIR}vteUpdater.sh"
		rm -f "$NEWSCRIPT" 2>/dev/null
		tar -xzf "$SCRIPTTMP" -C "$WORKDIR"

		if [ ! -s "$NEWSCRIPT" ]; then
			warning "The downloaded upgrade is invalid, skipped"
			return
		fi
	
		# adjust permissions
		local MYSELF=$(basename $0)
		local FILEMODE=$(stat -c "%a" $MYSELF)
		if ! chmod $FILEMODE "$NEWSCRIPT" ; then
			warning "Unable to set correct permission to the new script, self update skipped"
			return
		fi

		# create script to replace myself
		cat > updUpdater.sh << UPDEND
#!/bin/bash
# do a backup copy
BAKCOPY="$WORKDIR""vte_updater-$VERSION.sh.bak"
cp -f "vteUpdater.sh" "\$BAKCOPY" 2>/dev/null
# overwrite
if mv -f "$NEWSCRIPT" "vteUpdater.sh"; then
	echo
	echo "[INFO] The update script has been upgraded to version $NEWVERSION. Please launch it again now."
	rm -f -- \$0
else
	echo "[WARNING] The self update has failed, please run again the update script"
	echo "[WARNING] A backup copy of this script is in \$BAKCOPY"
	rm -f -- \$0
fi
UPDEND
   
		# execute it
		{
			debug "Installing new script version..."
			bash ./updUpdater.sh
			exit 0
		}

	else
		debug "Server version is $NEWVERSION, no need to update"
	fi
}

check_only_self_update () {
	if [ $ONLYUPGRADE -eq 1 -a $BATCH -eq 0 ]; then
		exit $RETCODE_OK
	fi
}

trap_handler () {
	
	echo
	if [ "$STATUS" = "UPDATING" ]; then
		warning "CTRL+C pressed during update"
		if [ $BATCH -eq 0 ]; then
			ask "Are you really sure to interrupt the update process? This can leave the VTE in a inconsistent state." "Y/N"
			if [ "$_RET" = "n" ]; then
				info "Resuming update..."
				return
			fi
		fi
		warning "Update interrupted by user"
	else
		warning "Script interrupted by user. No changes made to VTE"
	fi
	# delete partial backups
	if [ "$STATUS" = "CREATINGBACKUP" ]; then
		rm -f "$BACKUPFILE"
		rm -f "$BACKUPDBFILE"
		# vte & crons can still be stopped, enable them!
		restore_cron_and_vte
	fi
	# exit
	exit $RETCODE_BREAK
}

restore_cron_and_vte () {
	is_vte_disabled
	if [ $_RET -eq 1 ]; then
		enable_vte
		enable_cron
	fi
}


print_usage () {
	cat << USAGE
Usage: vteUpdater.sh [OPTIONS...]

Options
    -d N, --dest-revision=N   Update to revision N (if not specified, query the update 
                                server for the latest available version)
    -r N, --rollback=N        Try to rollback VTE to revision N (only if a backup was previously created)
          
          --disable-vte       Only set the VTE in maintenance mode
          --enable-vte        Only remove the maintenance mode
          
          --upd-package=PKG   Use the file PKG as the update package
          --src-package=PKG   Use the file PKG as the source package used during the file check phase.
                                Unless you specify the --skip-file-check option, you have to also provide
                                this file when using --upd-package.
          --del-package=PKG   Use PKG for the deleted files list (optional)
          
          --only-backup       Only create the backup of database and files (storage folder is excluded)
          --only-delete       Only delete obsolete files (detect current version, or use --del-package option)
          --only-file-check   Check only for modified files
          --only-upgrade      Like --force-upgrade, but exit immediately after
          
          --skip-file-check   Skip the check made on files that will be overridden. WARNING: you may loose
                                any customization in the VTE.
          --skip-file-remove  Don't remove obsolete files at the end of the update
          --skip-db-backup    Skip the backup of the database. WARNING: you may not be able
                                to restore VTE in case of errors. Use at your own risk!
          --skip-vte-backup   Skip the backup of VTE files. WARNING: you may not be able to restore 
                                VTE in case of errors. Use at your own risk!
          --skip-upgrade      Do not check for upgrades when starting
          --skip-cron         Do not wait the end of VTE cronjobs
          
          --force-upgrade     Check for upgrades when starting (by default the check is done only once a day)
          
    -t T  --tags=T            Additional tags to search during the file comparision. Can be specified multiple times.
          --pre-patches=DIR   Copy the files in DIR in the VTE directory BEFORE executing the schema update
                                No checks will be done for owerwritten files
          --post-patches=DIR  Copy the files in DIR in the VTE directory AFTER the schema update
                                No checks will be done for owerwritten files
          --www-user=USR:GRP  Set the user and group for the VTE files to USR and GRP
          --file-hashes=FILE  FILE contains md5 hashes to use to check for files differences.
                                For these files, only the hash check is performed
    -k X, --accesskey=X       Use X as the accesskey for the requests (--username must be used also)
    -u X, --username=X        Use X as the username for the requests (--accesskey must be also specified)
    -b    --batch             Enable batch mode: no user input required
                                (skip auto upgrade, assume yes on other questions)
    -v N, --verbosity=N       Set the verbositiy level to N (1=errors only, 4=debug, default=3)
          --version           Show version number
    -h,   --help              Show this help screen
USAGE
	local cmd=$(command -v base64 2>/dev/null)
	if [ -z "$cmd" ]; then 
		echo -e "\n                              This script does not have Super Shark Powers.\n"
	else
		echo -e "\n                              This script has Super Shark Powers.\n"
	fi
	exit $RETCODE_OK
}


show_shark () {
	local cmd=$(command -v base64 2>/dev/null)
	if [ -z "$cmd" ]; then 
		echo -e "\n                              This script does not have Super Shark Powers.\n"
	else
		# gzipped and base64 encoded ASCII shark
		cat << SHARK | base64 --decode | gunzip
H4sICAhCqFMAA3NxdWFsb19hc2NpaS50eHQApZRNbsQgDIX3nIKdJfzyLlF1OZuq6jV6/92AIcNP
TCp1vErI+55tDAnxzaBF+DcP4w+RYuXbkH8aGF+kAFwXiuxd8OKbB8XxYEJEFw2IZR39/CJU42lA
VUYkNnikdy0ojM6fNaFaaH7GqKGLEpa8+O72wAVZCna03cShoJcc1mHvUjWsiCNnf7FSMEOc02ha
slaHpbo1kc5jyyH5LAG3lIFXMorcVtgWa2/LBsxDXOWrmlv5jshH153kRHEt/y7NGTPFejLtw2V/
JvNOUX+11aAW/iGdK0QCT8TUctjghrDfCs6Z2D0xeV3Je1gukqLe4Z/vz4+vR6gdZa2J8kDDE9hO
EaUfBQAA
SHARK
	fi
}


parse_cmdline () {
	while getopts ":hbv:u:d:k:r:t:-:" arg; do
		case $arg in
			# special case for long options
			-)
				# split the value from the name
				local LONGOPT="$OPTARG"
				if [[ "$OPTARG" =~ = ]] ; then
					LONGOPT=${OPTARG%%=*}
					OPTARG=${OPTARG##*=}
				else
					OPTARG=""
				fi
				
				case "$LONGOPT" in
					enable-vte)
						ENABLEVTE=1
						;;
					disable-vte)
						ENABLEVTE=2
						;;
					only-backup)
						ONLYBACKUP=1
						;;
					only-delete)
						ONLYDELETE=1
						;;
					upd-package)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						USELOCALPKG="$OPTARG"
						;;
					src-package)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						USELOCALSRCPKG="$OPTARG"
						;;
					del-package)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						USELOCALDELPKG="$OPTARG"
						;;
					rollback)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						ONLYROLLBACK="$OPTARG"
						;;
					username)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						USERNAME="$OPTARG"
						;;
					accesskey)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						ACCESSKEY="$OPTARG"
						;;
					tags)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						EXTRATAGS+=("$OPTARG")
						;;
					pre-patches)
						if [ ! -d "$OPTARG" ]; then
							echo "The argument for $LONGOPT is not a valid directory"
							print_usage
						fi
						PREPATCHDIR="$OPTARG"
						;;
					post-patches)
						if [ ! -d "$OPTARG" ]; then
							echo "The argument for $LONGOPT is not a valid directory"
							print_usage
						fi
						POSTPATCHDIR="$OPTARG"
						;;
					file-hashes)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						FILEHASHES="$OPTARG"
						;;
					www-user)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						WWWUSERS="$OPTARG"
						;;
					skip-file-check)
						SKIPFILECHECK=1
						;;
					only-file-check)
						ONLYFILECHECK=1
						;;
					skip-file-remove)
						SKIPFILEREMOVE=1
						;;
					skip-db-backup)
						SKIPDBACKUP=1
						;;
					skip-vte-backup)
						SKIPVTEBACKUP=1
						;;
					skip-upgrade)
						SKIPUPGRADE=1
						;;
					force-upgrade)
						FORCEUPGRADE=1
						;;
					only-upgrade)
						FORCEUPGRADE=1
						ONLYUPGRADE=1
						;;
					skip-cron)
						SKIPCRON=1
						;;
					dest-revision)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						DESTREVISION=$OPTARG
						;;
					batch)
						BATCH=1
						;;
					verbosity)
						if [ -z "$OPTARG" ]; then
							echo "Missing argument for option --$LONGOPT"
							print_usage
						fi
						DEBUG=$OPTARG
						;;
					shark)
						show_shark
						exit $RETCODE_OK
						;;
					version)
						echo "VTE Automatic Updater  -- version $VERSION"
						exit $RETCODE_OK
						;;
					help)
						print_usage
						;;
					*)
						echo "Invalid argument: --$LONGOPT"
						print_usage
						;;
				esac
				;;
			# other options
			d)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				DESTREVISION=$OPTARG
				;;
			r)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				ONLYROLLBACK="$OPTARG"
				;;
			u)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				USERNAME="$OPTARG"
				;;
			t)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				EXTRATAGS+=("$OPTARG")
				;;
			k)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				ACCESSKEY="$OPTARG"
				;;
			b)
				BATCH=1
				;;
			h)
				print_usage
				;;
			v)
				if [ -z "$OPTARG" ]; then
					echo "Missing argument for option --$arg"
					print_usage
				fi
				DEBUG=$OPTARG
				;;
			?)
				echo "Invalid argument: -$OPTARG"
				print_usage
				;;
		esac
    done
}

# ------------------------ START ------------------------


# command line parsing
parse_cmdline $@

# color init and header
init_colors
print_text "${COLOR_BOLD}${COLOR_WHITE}VTE Automatic Updater  -- version $VERSION ${COLOR_NORMAL}"

trap trap_handler SIGINT

# INITIAL CHECKS
STATUS="INITIALIZING"
check_command stat
check_command touch
check_command head
check_command tr
check_command cut
check_command date
check_command grep
check_command sed
check_command sort
check_command tar
check_command unzip
check_command zipinfo
check_command gzip
check_command zcat
check_command find
check_command md5sum
check_command sha1sum
check_command php
check_command wget

# check options for various commands
check_commands_comp

# ...

check_vtedir "$VTEDIR"
create_workdir "$WORKDIR"
self_update
check_only_self_update
get_vte_revision "$VTEDIR"

if [ $ISCOMMUNITY -eq 1 ]; then
	info "Found VTE Community Edition at revision $VTEREVISION"
else
	info "Found VTE at revision $VTEREVISION"
fi

init_subfolder
init_log

write_log "Command invoked with arguments: $@"

check_only_backup
check_only_enable
check_only_rollback

check_use_package

check_only_delete


STATUS="FETCHPACKAGES"
get_dest_revision

fetch_package


info "Updating VTE to revision $DESTREVISION"

get_httpd_user


STATUS="UNPACKING"
unpack_src $SRCFILE
unpack_update $PACKAGEFILE
unpack_del $DELFILE

check_update_files


STATUS="CREATINGBACKUP"
disable_cron
disable_vte
create_vte_backup
create_db_backup

# NO-RETURN POINT
# So far nothing has been changed (apart from disabilng crons and vte)

STATUS="UPDATING"
do_update
enable_vte
enable_cron

STATUS=""
if [ $ROLLBACKOK -eq 1 ]; then
	exit $RETCODE_OKROLLBACK
else
	exit $RETCODE_OK
fi
