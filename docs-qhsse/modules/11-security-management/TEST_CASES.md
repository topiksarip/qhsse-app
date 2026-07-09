# Test Cases — Security Management

## Functional

1. Create valid record succeeds.
2. Create with missing required field fails.
3. Draft can be saved if allowed.
4. Submit changes status and creates number if needed.
5. Review/approve/reject follows permission.
6. Reject requires reason.
7. Close requires required evidence/action if configured.
8. Reopen works only for authorized role.

## Permission

1. User without view cannot access list/detail.
2. User own scope only sees own data.
3. Department scope only sees department data.
4. Contractor only sees company data.
5. Export follows permission.

## Integration

1. File upload/download respects permission.
2. Notification created for expected recipient.
3. Audit trail created for create/update/status change.
4. Comment appears in timeline.
5. Dashboard metric updates.
6. Export contains filtered data.

## Negative

1. Invalid status transition rejected.
2. Unauthorized file download rejected.
3. Duplicate number cannot occur.
