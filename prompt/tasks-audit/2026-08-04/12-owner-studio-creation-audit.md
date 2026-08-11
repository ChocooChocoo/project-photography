# Task 12 Plain-Language Audit — Owner Studio Creation

## Problem

When a studio owner began creating a studio and selected a municipality, the page reported that the owner did not have permission and sent the owner to the dashboard. This stopped the owner before the barangay choices could load.

## Result

The municipality lookup is now available to authenticated studio owners during first-studio creation. The lookup returns the available barangays and ZIP code normally.

The correction applies only to this read-only lookup. Existing protections for viewing, editing, updating, and deleting studios remain in place, as do the checks that confirm the visitor is an owner and that the studio-registration rules are satisfied.

## Checks completed

An automated check reproduced the original blocked request before the correction and then confirmed a successful response afterward. The response contained both barangays and the ZIP code for the selected municipality. The complete automated test run, route listing, formatting check, and final change review were also completed.

## User impact

Studio owners can now select a municipality, choose a barangay, and continue the studio creation process without the incorrect permission redirect. No roadmap document was changed.

## Conclusion

The owner studio-creation municipality interruption was corrected without broadening unrelated studio-management access.
