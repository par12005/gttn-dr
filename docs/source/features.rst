Features
========

GTTN-TPPS has many features that make data collection easier for administrators. Here are a few notable ones:

Data Types and Standards
------------------------

* Support for genotype and phenotype data and metadata
* Support for ontology standards, including the Minimum Information About a Plant Phenotyping Experiment (`MIAPPE`_)
* Support for standard genotyping file formats, such as .VCF
* Automatically submits data according to the Tripal CHADO database schema

Data Accessibility
------------------

* Data is standardized and stored in the local database so that other tools, for example, `CartograTree`_, can easily collect and analyze it
* Restricted access to users with the specific `gttn` user group.
* The studies can be queried or downloaded (flatfiles) through the Tripal interface
* Display both complete and incomplete submissions on 'GTTN-TPPS Submissions' user profile tab

User Friendliness
-----------------

* Map thumbnails for quick visual validation
* Auto-complete appropriate fields based on information from the user profile
* Load data from `NCBI`_ based on a provided BioProject accession number
* Automatically parse file contents for submission to the CHADO schema
* Save user progress on incomplete submissions
* Form flexibility to ensure only the minimum necessary information is being required, but users may provide additional information if they choose

Administrative Features
-----------------------
* Administrator panel to manually approve completed submissions
* Configuration page to specify file upload locations, TPPS Admin email, etc.

.. _NCBI: https://www.ncbi.nlm.nih.gov/
.. _MIAPPE: http://www.miappe.org/
.. _CartograTree: https://gitlab.com/TreeGenes/CartograTree
