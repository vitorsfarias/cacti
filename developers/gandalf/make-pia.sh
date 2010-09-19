#!/bin/bash
# -----------------------------------------------------------------------------
# make-pia.sh
# create the code for publishing PIA

# directory structure is as follows:
#    tags087g									tagged cacti version
#    PIA										PIA SVN code
#    cacti-plugin-arch							new PIA dir
#       +--> cacti-087g-PA-2.9.diff				the unified diff
#       +--> README
#       +--> LICENSE
#       +--> pa.sql								SQL for PIA
#       +--> files-0.8.7g						full files
#              +--> auth_changelog.php
#              +--> ...
#
# basedir	points to the tagged cacti version (unpatched)
# piadir	points to the PIA SVN code (full files, including PIA enhancements)
# newdir	points to the directory, that shall be created for the PIA distro
# diff		points to the unified diff
# files		points to the directory holding the full files
# -----------------------------------------------------------------------------

#--- path to everything
basePath="/home/reinhard/workspace"

#--- directory (tags) for base cacti code, e.g. tags087g
basedir=$1
if test "$basedir" == "" 
then
	basedir="tags087g"
fi
echo "BASEDIR $basedir"

#--- directory (trunk/main) for PIA code, e.g. PIA
piadir=$2
if test "$piadir" == "" 
then
	piadir="PIA"
fi
echo "PIA     $piadir"

#--- name of the new PIA directory, e.g. cacti-plugin-arch
newdir=$3
if test "$newdir" == "" 
then
	newdir="cacti-plugin-arch"
fi
echo "NEW     $newdir"

#--- name of the unified diff file, e.g. cacti-087g-PIA-2.9.diff
diff=$4
if test "$diff" == "" 
then
	diff="cacti-087g-PIA-2.9.diff"
fi
echo "DIFF    $diff"

#--- name of the files directory, e.g. files-0.8.7g
files=$5
if test "$files" == "" 
then
	files="files-0.8.7g"
fi
echo "FILES   $files"


#--- cd to working directory
echo cd $basePath
cd $basePath

#--- create new directory, where plugin arch shall reside
echo mkdir "$newdir"
mkdir "$newdir"

#--- create the diff, ignoring files available in base only and excluding some stuff
echo diff -burP "$basedir/" "$piadir/" --exclude-from="diff-exclude" | grep -v "Nur in" > "$newdir/$diff"
diff -burP "$basedir/" "$piadir/" --exclude-from="diff-exclude" | grep -v "Nur in" > "$newdir/$diff"

#--- create files directory
echo mkdir "$newdir/$files"
mkdir "$newdir/$files"

#--- copy full files, except for piadoc
echo rsync -a "$piadir/" "$newdir/$files" --exclude=.svn --exclude=piadoc
rsync -a "$piadir/" "$newdir/$files" --exclude=.svn --exclude=piadoc

#--- put piadoc into main dir
echo rsync -a "$piadir/piadoc/" "$newdir/$files" --exclude=.svn
rsync -a "$piadir/piadoc/" "$newdir" --exclude=.svn
