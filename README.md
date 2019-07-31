# WUSM Project To Project Field Replication Module

An external module to copy Fields from one Project to another Project as a source and destination relationship.  If adding fields after SOURCE creation and do not want those fields copied then use configuration in the SOURCE project to exclude the extra fields. Both SOURCE and DESTINATION projects MUST HAVE the same field variable names and data types matching 
to sucessfully copy data from SOURCE to DESTINATION.

********************************************************************************

## Getting Started

You will need **TWO** projects.

>One **source** project

>One **destination** project

Each project MUST HAVE matching fields, the variable names, and data types.

The source project MAY HAVE more fields than the destination project, however,
will REQUIRE exclusion of those fields to remove them from the copy process.
In the configuration for the EM, use "Field variable to exclude from copy" to set which fields to ignore or exclude from the copy processing.


**Configuration**

Source Project: MUST set a Destination project ID (via a drop down choice list, which are limited to what a user has access to)

Destination Project: MUST set an allowed Source project ID (via a drop down choice list of projects, which are limited to what a user has access to)


**Exclude copy functionality**

Source Project

Can specify a field or fields to ignore in the copy process. (Fields are selected from a drop down choice list of fields)


## Setup Notes

**IMPORTANT NOTE:**
**The owner of a project must be the one to configure for the Source and Destination projects, as the drop-downs only allow that user to make the correct setting choices.**

**NOTE: Instrument names MUST NOT contain a slash.**  Example:  My/InstrumentName  (should be: MyInstrumentName or My Instrument Name or any variant without the slash).

**NOTE:** Whenever the "**Overwrite Destination with empty fields** (blank fields will overwrite filled in data on the destination):" option is selected in the module configuration AND there is an instrument that contains any checkbox field that is assigned to any other Event besides Event1, the module cannot successfully use the function "save_data" on the destination project.
This is a **verified bug that was fixed in REDCap version 8.4.4**.

(If you are using REDCap version less than 8.4.4, as a short term workaround you may have the destination project have the instruments enabled for both Event 1 as well as the originally intended Event, then the module can successfully use the save_data function.)

********************************************************************************

## Prerequisites

Two projects, with matching fields (variable names and data types).

Configuration settings to allow a source project to copy to a destination project 
>(this is set in the destination project).

Configuration settings in the source project to identify which project is the destination 
>(this is set in the source project).
********************************************************************************

### Authors

* **David Heskett** - *Initial work*

### License

This project is licensed under the MIT License - see the [LICENSE](?prefix=wusm_project_to_project_field_replication&page=LICENSE.md) file for details

### Acknowledgments

* Inspired by WUSM REDCap Team.

