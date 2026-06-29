**Non-Functional Requirements (NFRs)**

**NFR-1 Performance**

- Initial board load should complete within 2 seconds under normal
  conditions.

- Task creation, update, and movement operations should complete within
  500ms.

- Search results should be returned within 500ms.

- Activity feeds and notifications should load within 1 second.

**NFR-2 Real-Time Responsiveness**

- Task movements should be visible to connected users within 1 second.

- New comments should appear to connected users within 1 second.

- Presence information (online users) should update within 5 seconds.

- Typing indicators should update within 500ms.

**NFR-3 Scalability**

The system should comfortably support:

- 100 concurrent authenticated users.

- 50 active users viewing the same board.

- 100 workspaces.

- 1000 tasks per board.

- 100 comments per task.

The architecture should allow future horizontal scaling without major
redesign.

**NFR-4 Reliability**

- Concurrent task updates must not corrupt data.

- Duplicate requests should not create duplicate records.

- Task ordering must remain consistent after concurrent movements.

- Activity logs must accurately reflect user actions.

- System failures should not leave partially completed operations.

**NFR-5 Availability**

- Temporary websocket disconnections should not require page refreshes.

- Clients should automatically reconnect to real-time services.

- Users should continue viewing cached board data during brief
  connection interruptions.

**NFR-6 Security**

- All protected actions require authentication.

- All sensitive actions require authorization checks.

- Users may only access workspaces they belong to.

- File uploads must be validated.

- User input must be sanitized and validated.

- Rate limiting should protect public endpoints.

**NFR-7 Maintainability**

- Business logic must not reside in controllers.

- Features should be organized by domain.

- Core actions should be independently testable.

- The codebase should support adding new collaboration features without
  major refactoring.

**NFR-8 Observability**

- Important business actions must be logged.

- Errors must be recorded for troubleshooting.

- User activity should be traceable through an activity feed.

- Administrative audit information should be available for critical
  actions.

**NFR-9 Consistency**

- Clients should converge to the same board state after updates.

- Stale updates must not overwrite newer data.

- Event ordering must be preserved where required.

- Board state should remain valid after simultaneous user actions.

**NFR-10 Resource Efficiency**

- High-frequency frontend events must be throttled.

- Expensive operations should be processed asynchronously through
  queues.

- Frequently accessed data should be cacheable.

- Real-time broadcasts should be minimized to necessary recipients.
