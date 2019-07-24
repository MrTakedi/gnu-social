# Activity Moderation

## About

This plugin handles the DELETE verb interaction.

## Todo

+ Full conversion to "ActivityVerbHandlerPlugin"

Contrary to what happened to other notice functionality, the delete
core-logic wasn't fully refactored to be handled by this plugin.
Instead, it was sort of quickly adapted by introducing a new delete
function in the Notice class and making the right calls where needed.
In the current state, the plugin fully controls the UI and logic
behind the notice delete-option form, but it does so while
maintaining the adaptations just described.
