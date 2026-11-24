# Task 03 — Pack Composition and Publication

Implement draft composition editing and publication lifecycle.

Requirements:
- Components are standalone products only; reject Pack IDs and all other entities.
- Minimum two distinct components for publication.
- Positive component quantities.
- Explicit component variant mapping for each Pack variant/component.
- Validate mapping ownership and eligibility.
- Draft composition and mappings can be edited.
- First publication permanently locks composition and mappings.
- Deactivation/unpublishing/reactivation never unlocks.
- Backend/service-level enforcement must cover all entry points.
- Other permitted catalog attributes remain editable.
- Publication and composition updates serialize safely under concurrent requests.

Implement tests for every applicable CMP/PUB/VAR scenario in testing-strategy.md. Execute and report.
