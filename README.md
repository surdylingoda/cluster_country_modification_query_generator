# Cluster query creator
# Installation
Run `composer install`
## Usage
1. Add records to package_data.json

Example data:
```json
{
  "whitelist": {
    "01JJ1Y2VXY3RFJQN4W51564R4K": "JIM",
    "01JJ1XT1EB10N12XF41JG3X7X3": "KLN",
    "01JJ1WDWCBK1E99S1J16QWAX71": "JIM",
    "01JJ1W3Z0N221RS5MM4N74CTGP": "KLN",
    "01JJ1S2ACR9Q9EFGKQE37DWG08": "JIM",
    "01JJ1RVSSRM10DXMGCBV6SK8H8": "KLN",
    "01JJ1R9VY75XJAHR7MDH1WNRNG": "JIM",
    "01JJ1R1AETFATGQVJCVPNWR114": "KLN"
  },
  "blacklist": {
    "01JJ1XJ975Z561Z311PY6EDC4B": "JIMKLN",
    "01JJ1VRZVG0M75C36JXVSD4KHA": "JIMKLN",
    "01JJ1RM0N64SZG8PP733BRG3YG": "JIMKLN",
    "01JJ1Q9Z1M5KD1V3RB5WZ8QSFP": "JIMKLN"
  }
}
```
2. Run ./script.ph
3. The output will appear in SQL file in `output` folder with name in format of `package_cluster_query_{$timestamp}.sql`

Example output:
```sql
-- whitelist 01JJ1R1AETFATGQVJCVPNWR114 KLN
INSERT IGNORE INTO package_visibility_whitelist (package_id, country_id)
(SELECT DISTINCT UNHEX('01948380a9da7ab50bee4cddabcc0424'), c.id
   FROM country c
   JOIN geo_country gc ON gc.country_code = c.alpha2_code
   JOIN geo_cluster_country gcc ON gcc.geo_country_id = gc.id
   JOIN geo_cluster gc2 ON gc2.id = gcc.geo_cluster_id
   WHERE gc2.cluster_name IN ('K','L','N'));

-- blacklist 01JJ1XJ975Z561Z311PY6EDC4B JIMKLN
INSERT IGNORE INTO package_visibility_blacklist (package_id, country_id)
(SELECT DISTINCT UNHEX('019483d924e5f94c1f8c21b78ce6b08b'), c.id
   FROM country c
   JOIN geo_country gc ON gc.country_code = c.alpha2_code
   JOIN geo_cluster_country gcc ON gcc.geo_country_id = gc.id
   JOIN geo_cluster gc2 ON gc2.id = gcc.geo_cluster_id
   WHERE gc2.cluster_name IN ('J','I','M','K','L','N'));
```