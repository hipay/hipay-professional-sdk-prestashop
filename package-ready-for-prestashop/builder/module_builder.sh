#!/usr/bin/env bash

version=1.0.0

function cleanAndPackage()
{
    cp -R src/hipay_professional hipay_professional


    ############################################
    #####          CLEAN CONFIG             ####
    ############################################
    if [ -f hipay_professional/config_fr.xml ]; then
        rm hipay_professional/config_fr.xml
    fi

    ############################################
    #####          CLEAN IDEA FILE           ####
    ############################################
    if [ -d hipay_professional/nbproject ]; then
        rm -R hipay_professional/nbproject
    fi

    if [ -d hipay_professional/.idea ]; then
        rm -R hipay_professional/.idea
    fi

    find hipay_professional/ -type d -exec cp index.php {} \;
    zip -r package-ready-for-prestashop/hipay_professional-$version.zip hipay_professional
    rm -R hipay_professional
}

function show_help()
{
	cat << EOF
Usage: $me [options]

options:
    -h, --help                        Show this help
    -v, --version                     Configure version for package
EOF
}

function parse_args()
{
	while [[ $# -gt 0 ]]; do
		opt="$1"
		shift

		case "$opt" in
			-h|\?|--help)
				show_help
				exit 0
				;;
				esac
		case "$opt" in
			-v|--version)
              	version="$1"
				shift
				;;
		    esac
	done;
}

function main()
{
	parse_args "$@"
	cleanAndPackage
}

main "$@"

