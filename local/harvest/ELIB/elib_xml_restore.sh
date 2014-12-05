#!/bin/sh

#?# . /etc/profile
#?# export VUFIND_HOME=/home/github/uf-vault
#?# export VUFIND_LOCAL_DIR=/home/github/uf-vault/local


### Step-1: copy XML-files from "processed" subdir to working subdir ###

# ELIB/KEV 
cp $VUFIND_LOCAL_DIR/harvest/ELIB/KEV/processed/* $VUFIND_LOCAL_DIR/harvest/ELIB/KEV/

# ELIB/UCH_{HUM,EST,FM}
cp $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_HUM/processed/* $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_HUM/
cp $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_EST/processed/* $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_EST/
cp $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_FM/processed/*  $VUFIND_LOCAL_DIR/harvest/ELIB/UCH_FM/

# ELIB/F_C
cp $VUFIND_LOCAL_DIR/harvest/ELIB/F_C/processed/* $VUFIND_LOCAL_DIR/harvest/ELIB/F_C/

# WOS
cp $VUFIND_LOCAL_DIR/harvest/WOS/processed/* $VUFIND_LOCAL_DIR/harvest/WOS/

### Step-2: call indexing script to perform restored files ###
### P.S. If ignore below script => indexing from cron at night !!!

#sh $VUFIND_LOCAL_DIR/harvest/ELIB/elib_xml_indexing.sh
    $VUFIND_LOCAL_DIR/harvest/ELIB/elib_xml_indexing.sh
