# Discovery scripts

Run from the project root with Python 3.10+ and no installed packages:

```sh
rtk proxy python3 scripts/discovery/analyze.py
rtk proxy python3 scripts/discovery/report.py
rtk proxy python3 scripts/discovery/verify.py
```

`analyze.py` reads the immutable local XML and writes the full-corpus evidence JSON. `report.py` renders measured tables and explicitly editorial recommendations, plus a 30-record ID/title/URL selection manifest. `verify.py` independently recounts major source structures, checks the fixed original hash and selected records, and verifies local report links; it writes `docs/discovery/verification.json`. A changed source file requires a deliberate baseline/hash review rather than silently accepting a different snapshot.

All three scripts are offline and use only the standard library. They never resolve the external DTD, crawl resource links, create a database, or perform retrieval/model inference. Remote DTD access failed during discovery; documentation fallbacks and policy source links are noted in the reports. The source file is never opened for writing.

Coverage and repetition include absent fields as zero per topic. Resource identity uses exact URL strings; domain means hostname retaining subdomains. Alias normalization is NFKC + casefold + collapsed whitespace within language. HTMLParser is tolerant, not a sanitizer or validator. URL syntax warnings are conservative heuristics, not availability tests. The evidence JSON intentionally includes exhaustive value inventories and inferred pair lists, so it is larger than the source and is not a POC runtime asset.

The fixed editorial report date corresponds to this snapshot's discovery run. Do not reuse the recommendations or hard-coded summary claims for a new snapshot without re-reviewing them. No implementation beyond analysis/reporting has been authorized.
