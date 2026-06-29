The goal is to define:

Entities\
Relationships\
Cardinality\
Ownership\
Constraints

**1. User**

Represents a platform user.

Relationships:

User\
├─ hasMany Memberships\
├─ hasMany Comments\
├─ hasMany Activities\
├─ hasMany Notifications\
└─ belongsToMany Tasks (assignee)

**2. Workspace**

Top-level collaboration boundary.

Relationships:

Workspace\
├─ hasMany Memberships\
├─ hasMany Boards\
├─ hasMany Invitations\
└─ hasMany Activities

Constraint:

Workspace cannot exist without an Owner.

**3. Membership**

Connects User and Workspace.

Represents:

User\
↔\
Workspace

with role information.

Relationships:

Membership\
├─ belongsTo User\
└─ belongsTo Workspace

Constraint:

One user can have only one membership\
per workspace.

**4. Board**

Project container.

Relationships:

Board\
├─ belongsTo Workspace\
└─ hasMany Columns

Constraint:

Board must belong to exactly one workspace.

**5. Column**

Workflow stage.

Relationships:

Column\
├─ belongsTo Board\
└─ hasMany Tasks

Examples:

Todo\
Doing\
Review\
Done

Constraint:

Column names should be unique within board.

**6. Task**

Core business entity.

Relationships:

Task\
├─ belongsTo Column\
├─ hasMany Comments\
├─ hasMany Activities\
├─ belongsToMany Users (assignees)\
└─ belongsToMany Labels

Constraint:

Task belongs to exactly one column.

**7. Comment**

Discussion around work.

Relationships:

Comment\
├─ belongsTo Task\
└─ belongsTo User

Constraint:

Comment cannot exist without task.

**8. Activity**

Audit/history record.

Examples:

Task Created\
Task Moved\
Comment Added\
Member Invited

Relationships:

Activity\
├─ belongsTo Workspace\
├─ belongsTo User\
└─ references target entity

## **Important Design Choice**

I recommend a polymorphic target.

Example:

Activity\
↓\
\
Task\
Comment\
Board\
Workspace

Laravel:

activityable

This avoids:

task_id\
comment_id\
board_id\
workspace_id

all in one table.

------------------------------------------------------------------------

# 9. Notification

Represents attention-required events.

Relationships:

Notification\
└─ belongsTo User

Examples:

Assigned task\
Mentioned in comment\
Invitation received

------------------------------------------------------------------------

# 10. Invitation

Used before membership exists.

Relationships:

Invitation\
└─ belongsTo Workspace

After acceptance:

Invitation\
↓\
Membership

Constraint:

Cannot invite existing member.

------------------------------------------------------------------------

# 11. Label

Task categorization.

Examples:

Bug\
Feature\
Backend\
Urgent

Relationships:

Label\
├─ belongsTo Workspace\
└─ belongsToMany Tasks

------------------------------------------------------------------------

# Many-to-Many Relationships

We currently have only two.

## Task Assignments

Task\
↔\
User

Pivot:

task_user

------------------------------------------------------------------------

## Task Labels

Task\
↔\
Label

Pivot:

label_task

(or label_task, task_label; naming can be decided later)

# Aggregate Ownership

This is a DDD-ish concept but useful.

Think:

Workspace\
owns\
Boards\
Memberships\
Invitations\
\
Board\
owns\
Columns\
\
Column\
owns\
Tasks\
\
Task\
owns\
Comments

Meaning:

Delete Workspace\
↓\
Delete Boards\
↓\
Delete Columns\
↓\
Delete Tasks

# Invariants (Business Rules)

These are very important.

### Workspace

Must always have exactly one owner.

------------------------------------------------------------------------

### Membership

One membership per user per workspace.

------------------------------------------------------------------------

### Board

Must belong to a workspace.

------------------------------------------------------------------------

### Task

Must belong to a column.

------------------------------------------------------------------------

### Assignment

Assigned user must belong to same workspace.

------------------------------------------------------------------------

### Comment

Author must belong to workspace.
