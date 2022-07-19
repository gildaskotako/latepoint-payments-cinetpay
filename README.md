# latepoint-payments-hiboutik

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/a2c8d038eb994541bd87e30bb9328812)](https://app.codacy.com/gh/arthuRHD/latepoint-payments-hiboutik?utm_source=github.com&utm_medium=referral&utm_content=arthuRHD/latepoint-payments-hiboutik&utm_campaign=Badge_Grade_Settings)

```mermaid
sequenceDiagram
Latepoint->>+AddOn: Get payments with hooks
AddOn->>+Hiboutik: Send payments over HTTPS
Hiboutik->>-AddOn: Catch REST API Response
AddOn->>-Latepoint: Notify over hooks
AddOn->>+Hiboutik: Triggers auto sync over cron job
AddOn-->>+Hiboutik: Retrieve cutomers, payments and categories
Hiboutik-->>-AddOn: Verify data
Hiboutik->>-AddOn: Store data inside local database
AddOn->>+Latepoint: Sync data from local database
```
