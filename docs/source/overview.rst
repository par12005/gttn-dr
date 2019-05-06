Overview
========

GTTN-TPPS is a data collection tool developed with the goal of collecting high-quality reference data for the purposes of timber tracking and identification. The module collects 4 different types of data:

.. image:: ../../images/overview_diagram.png

Genotype and Phenotype Data
---------------------------
The core of the data that GTTN-TPPS will collect is the Genotype and Phenotype data. The genotype data might include data in the form of SNPs, genotyping assays, SSRs, etc. The phenotype data might include data in the form of DART data, wood anatomy, etc. DART data could include a series of files with peak data for various isotopes found in a DART scan. Wood anatomy data could include images of microscope slides, along with the specific anatomical features found within the slide in question.

Georeferenced Accessions
------------------------
When the Genotype and Phenotype Data are submitted through GTTN-TPPS, it will come along with georeferenced accessions of the trees which were sampled in order to obtain the data. Georeferenced Accessions will usually be submitted in the form of an excel table, mapping each tree identifier to a latitude/longitude coordinate. The georeferenced accessions will be integrated with the Genotype and Phenotype Data in the REF Database.

Method-Specific Metadata
------------------------
In addition to Georeferenced Genotype and Phenotype Data, each GTTN-TPPS submission will also include metadata which is specific to the analysis method used to obtain the data. For example, if the submission includes DART data, then part of the metadata GTTN-TPPS will collect might be the settings of the DART machine used to obtain the data, or if the submission includes Genotyping by Sequencing data, then part of the metadata GTTN-TPPS will collect might include the type of Genotyping by Sequencing: ddRAD, RAD, NextRAD, etc. For more details, you can view this `Metadata Document`_ which was put together at the March 2019 GTTN workshop in Koli, Finland.

Data Access Options
-------------------
The 4th type of data that will be collected in a GTTN-TPPS submission is the Data Access and Authorization Options. Here users will be allowed to select which organizations within the GTTN network are allowed to see the data being submitted, whether the data will be published to TreeGenes, etc.

.. _Metadata Document: https://docs.google.com/spreadsheets/d/1-D5lyZuEZDnVxGXNqia997vm1Wpu9a7XcHPOQ_pQSq0/edit?usp=sharing
