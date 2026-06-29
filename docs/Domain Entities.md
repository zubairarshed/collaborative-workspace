# **Domain Entities**

A domain entity is a thing that the business cares about and can
identify uniquely.

Or

An Entity is something with:

- Identity

- State

- Behavior

i.e., User, Workspace, Task, Board etc. These have a lifecycle. A task
has an **identity** (task#123), **state** (title, description, due date,
status), **behavior** (move(), assign(), complete(), archive()).
Therefore, Task = Domain Entity.

**Core Domain**

- User

- Workspace

- Membership

- Board

- Column

- Task

- Comment

- Activity

- Notification

- Invitation

- Label

### Future Domain

- Attachment

- Checklist

- ChecklistItem

- Reminder

- Template
