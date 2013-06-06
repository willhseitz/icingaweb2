#!/bin/sh

set -o nounset

pushd `dirname $0` > /dev/null
DIR=`pwd`
popd > /dev/null
CASPER=$(which casperjs)
INCLUDE=""
EXCLUDE=""
VERBOSE=0
BUILD=0

if [ ! -x $CASPER ]; then
    echo "CasperJS is not installed but required to run frontend tests\n"\
"Take a look at http://casperjs.org/installation.html to see how the installation works for your system"
    exit 1
fi;

PARAM="0"
for arg in $@;do
    if [ ! "$PARAM" == "0" ]; then
        export $PARAM=$arg
        PARAM="0"
        continue 
    fi; 
    case $arg in
        --verbose)
            VERBOSE=1
            ;;
        --include)
            PARAM="INCLUDE"
            continue
            ;;
        --exclude)
            PARAM="EXCLUDE"
            continue
            ;;
        --build)
            BUILD=1
            continue
            ;;
        **)
            if [ "$arg" != "--help" ]; then
                echo "Unknown option $arg" 
            fi;   
            echo "Testrunner for interface tests: ./$0 [--verbose] [--include %include%] [--exclude %exclude%] [--build]"
            echo "\t\t --verbose \t\t\t Print verbose output when testing"
            echo "\t\t --include %filelist%\t\t Include only files matching this patterns"
            echo "\t\t --exclude %filelist%\t\t Exclude files matching this patterns"
            echo "\t\t --build \t\t\t Write test results to ../../build/log/casper_results.xml"
            echo "\t\t --help \t\t\t Print this message"
            exit 1

    esac;
done;

EXEC="$CASPER test"

#
# If build is set, the results are written for our jenkins server
#
if [ $BUILD -eq 1 ];then
    mkdir -p $DIR/../../build/log
    EXEC="$EXEC --xunit=$DIR/../../build/log/casper_results.xml"
fi;
if [ "$PARAM" !=  "0" ]; then
    echo "Missing parameter for $PARAM"
    exit 1
fi;

cd $DIR
FILELIST=""
#
# Default : Run regression and cases directory
#
if [ "$INCLUDE" == "" -a "$EXCLUDE" == "" ];then
    FILELIST="./cases ./regression"
fi; 

#
#  Include patterns set with the --include directive 
#
if [ "$INCLUDE" != "" ];then
    NAME="\("
    GLUE=""
    for INC in $INCLUDE;do
        NAME="$NAME${GLUE}${INC}.*js"
        GLUE="\|"
    done;
    NAME=$NAME"\)$"
    FILELIST=`find . | grep "$NAME"`
fi;

#
#   Exclude patterns that match the include directive
#
if [ "$EXCLUDE" != "" ];then
    NAME="\("
    GLUE=""
    for EXC in $EXCLUDE;do
        NAME="$NAME${GLUE}${EXC}.*js"
        GLUE="\|"
    done;
    NAME=$NAME"\)$"
    if [ "$FILELIST" == "" ]; then
        FILELIST=`find .|grep ".*js$"`
    fi
    FILELIST=`echo $FILELIST | grep -v "$NAME"`
fi;

echo $EXEC $FILELIST
$EXEC $FILELIST 

exit 0
