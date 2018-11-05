# GTTN-DR Demo
[![Documentation Status](https://readthedocs.org/projects/gttn-dr/badge/?version=latest)](https://gttn-dr.readthedocs.io/en/latest/?badge=latest)

A collaboration between University of Connecticut's Plant Computational Genomics
lab and the Global Timber Tracking Network. Allows for secure and convenient 
submission of multiple tree descriptors through a web-based interface.

This module is a modified extension of the Tripal Plant PopGen submit Pipeline (TPPS), which can be found here: http://tpps.rtfd.io

This module is currently in demo form, meaning that the data is not submitted to the TreeGenes database, and there is an additional "results" page, which displays the data in short text when the user clicks "Submit".

The first page of the module prompts the user for information about each species they are uploading data about, as well as a file with location information and unique identifiers for each tree.

The second page of the module asks for information about the sampling and analysis dates of each species, as well as any phenotypic or genotypic data files the users have. 

Unlike TPPS, this form can only be accessed by members of the 'gttn' or 'administrator' groups on the TreeGenes site.

More detailed outline: https://docs.google.com/document/d/1A9rjqS7aFw4TrowH47qcfhozWTtFrEwJr5zBGHnDZVI/edit?usp=sharing

Done:
    -load trees spreadsheet from user
    -read columns from spreadsheet
    -sampling date
    -analysis date
    -ask if data should be published
    -Genotype, Phenotype, or both?
    -Genotype: SNPs or SSRs
    -SNPs: Assay or resequencing
    -SNPs VCF
    -SNPs spreadsheet
    -SSRs spreadsheet
    -Phenotype spreadsheet
    -define phenotypes
    -compare tree ids in phenotype to metadata
    -login requirements
    -'gttn' group on tgwebdev
    -species field
