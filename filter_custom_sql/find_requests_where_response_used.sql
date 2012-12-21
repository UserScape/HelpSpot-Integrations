/*
Use this SQL in a custom WHERE clause of a filter/report to show requests where a specific response was used.

You must replace the ### with a response ID
*/

xRequest IN (SELECT xRequest FROM HS_Stats_Responses WHERE HS_Stats_Responses.xRequest = xRequest AND HS_Stats_Responses.xResponse = ####)
