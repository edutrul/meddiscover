#!/usr/bin/env python3
"""Record approved English selection; never import content or alter history."""
import hashlib
import json
from pathlib import Path
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[2]
manifest = json.loads((ROOT / "docs/discovery/minimal-selection.json").read_text())
raw = (ROOT / "data/raw/mplus_topics_2026-11-24.xml").read_bytes()
if hashlib.sha256(raw).hexdigest() != manifest["source_sha256"]:
    raise SystemExit("Source checksum differs from discovery baseline")
selected = [t for t in manifest["topics"] if t["language"] == "English"]
assert len(selected) == 24
index = {t.get("id"): t for t in ET.fromstring(raw)}
for record in selected:
    topic = index[record["id"]]
    assert all(topic.get(key) == record[key] for key in ("title", "language", "url"))
sites = [s for t in selected for s in index[t["id"]].findall("site")]
output = {
    "status": "Approved English-only selection; not imported",
    "source_sha256": manifest["source_sha256"],
    "attribution": manifest["attribution"],
    "metrics": {"topics": len(selected), "resource_assignments": len(sites),
                "unique_resource_urls": len({s.get("url") for s in sites})},
    "topics": selected,
}
(ROOT / "docs/discovery/implementation-selection.json").write_text(
    json.dumps(output, indent=2) + "\n")
print(json.dumps(output["metrics"]))
