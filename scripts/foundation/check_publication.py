#!/usr/bin/env python3
"""Reject accidentally tracked local datasets, runtime state and secrets."""
from pathlib import PurePosixPath
import subprocess

paths = subprocess.check_output(["git", "ls-files", "-z"]).decode().split("\0")
for name in filter(None, paths):
    path = PurePosixPath(name)
    forbidden = (
        name.startswith(("data/raw/", "vendor/", "web/core/", ".local/"))
        or name in ("docs/discovery/dataset-profile.json", "docs/discovery/verification.json")
        or path.name in ("auth.json", "settings.ddev.php", "settings.local.php", ".env")
        or path.name.startswith(".env.")
        or "/files/" in name
        or name.endswith((".sql.gz", ".key"))
    )
    if forbidden:
        raise SystemExit(f"Forbidden tracked artifact: {name}")
print("PASS: tracked publication boundaries")
