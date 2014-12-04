#!/bin/sh

. /etc/profile

export VUFIND_HOME=/home/github/uf-vault
export VUFIND_LOCAL_DIR=/home/github/uf-vault/local

sh $VUFIND_HOME/harvest/batch-delete.sh $1 $2
