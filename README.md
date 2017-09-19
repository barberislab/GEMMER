# GEMMER

## How to interact between local and VPS
Set up the VPS as a remote for your git repository: http://toroid.org/git-website-howto
* init a bare git repo on the VPS
* add a hooks/post-receive file with
`#!/bin/sh
GIT_WORK_TREE=/var/www/www.example.org git checkout -f`
* Make hook executable
`chmod +x hooks/post-receive`
* Make sure you can ssh into the VPS without password
* add the bare git repo as a remote and push to it

From then on: 
Change locally. Commit (rebase) and sync. 



## General notes on steps & requirements for installation on a VPS
* Get a VPS
* install the required packages (Apache etc.)
* git clone this repository and set it to be the folder you serve the website from in Apache. 
* make sure you rename the main folder gemmer, don't leave it at GEMMER
`mv GEMMER/ gemmer`
* run `./install_script` to get the ownership of directories correc

