A collaboration between University of Connecticut's Plant Computational Genomics
lab and the Global Timber Tracking Network. Allows for secure and convenient 
submission of multiple tree descriptors through a web-based interface.

Outline:
Page 1: “User Login and/or Registration”
Purpose:  The highest priority for GTTN users is likely security. The first step they should do is either register under an appropriate institution or input their login information.
Requirement: Login is associated with a specific organization and the overall GTTN group.  By default, groups will only be able to share data within their organization.  In general, GTTN data can be stored independently and released only as indicated (below).

For the sake of the demo, we just need to begin with one active/enabled user.

Species Selection (genus/species) and Tree Metadata
Required: at least one defined species to begin process
Add Species: button → adds an instance of the “Genus/Species” selection
Species: textfield with autocomplete → retrieves items in columns “genus” and “species” from chado.organism, where “genus” matches with any text before the first space in the text field, and “species” matches with any text after the first space.  References the species database in TreeGenes.
(B) Sampling Metadata Spreadsheet load (flexible formats: xlsx, csv, tsv):
Required: tree identifiers with approximate/exact location information
Constraint: one sheet per species
User defined column for Tree IDs*
User defined column for Tree locations (exact or approximate).  If exact, lat/long fields
IF exact, Location type: drop-down menu → “Longitude/Latitude(WGS 84)”, “Longitude/Latitude(NAD 83)”, “Longitude/Latitude(ETRS 89)” and “Custom”.
AND/IF the user selects “Custom”:
Select: Country, State/Province, Feature (Forest, Park, etc)
Optional fields defined by submitter for a ‘flexible submit’
(C) Required Question: Do you want to publicly release the species and location 
      data?
If public, should we obscure the exact location?





Page 2: “Genotype, Phenotype”

(A) Required: Select Data Type(s): check-box:
Genotype
Phenotype
Both
(B) Sampling date: for one species or all species uploaded (month/year) - optional
(C) Analysis date: for one or all species uploaded (month/year) - optional
(D) Genotypes
Required: Select Marker Type: SSRs SNPs
SNP options:
Select: Resequencing OR Array/Assay
Resequencing = Load VCF text file format
Array/Assay = Load flexible spreadsheet
IF Array/Assay: Question: Select Genotyping Platform: Affy/Axiom, Illumina, MassArray
Field options (blue are required): TreeID, probe sequence (1), probe sequence (2), alleles, scaffold/chromosome, position
(E) Phenotypes
Required: Select Phenotype:
Isotope (Sample data provided by Celine)
TreeIDs and name of the isotope as columns
Others? Image data etc.

(F) Metadata: Upload of text/images files with a definition field by user (will not be curated/stored in TreeGenes)


Validation for page 2:
TreeID validation across all files
TreeID can have missing phenotype or genotype but all genotypes/phenotypes must have a TreeID with metadata



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
