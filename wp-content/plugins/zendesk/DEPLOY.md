# Deployment 🚀

| Resource | Link                                                                |
|:---------|:--------------------------------------------------------------------|
| Wiki     | https://zendesk.atlassian.net/wiki/display/MI/Wordpress+Integration |
| Travis   | https://travis-ci.com/zendesk/zendesk_wordpress                     |
| Samson   | https://samson.zende.sk/projects/zendesk_wordpress                  |
| Support  | https://wordpress.org/support/plugin/zendesk                        |
 
## General Rules

- :loudspeaker: Notify `mintegrations@zendesk.com` before any deployment or when something goes wrong.
- :speech_balloon: Notify [#mintegrations-team](https://zendesk.slack.com/messages/C169MJEF8) if you have questions, problems, RAs, permissions.
- :alarm_clock: Our team is based in Manila (UTC+08:00) (avoid off-hours deployments).
- :cop: A deploy-buddy is mandatory in order to perform a deployment in production.
- :no_entry: Always verify there is no code-freeze in place, or strict-mode is enabled.
 
## Restrictions
 
Both code-freeze and strict-mode are periods of time when the rules for making changes to the code, including deployments, infrastructure or related resources become more strict.
 
### Code Freeze
 
Only production hotfixes and critical changes are allowed to be merged and deployed. This usually happens around big holidays like Christmas.
 
### Acceptance Criteria
 
Take time to verify all the PRs included in this deployment.
 
## Stages

| Stage      | Location                      | Notes                                                                |
|:-----------|:------------------------------|:---------------------------------------------------------------------|
| Staging    | wp-inbox.zendesk-store.com    | A copy plugin is installed in this wordpress site to allow testing.  |
| Production | wordpress.org                 | The plugin is published in wordpress.org                             |                                                                
 
## Deployment Process

Staging is automatically deployed whenever a PR is merged and is green on travis. Production on the other hand, have to be deployed manually and should always have a deploy buddy.

### Staging

1. Deploys automatically after a PR is merged and is green on travis.
2. Wait until the deploy is done successfully.
3. Test the plugin on [http://wp-inbox.zendesk-store.com](http://wp-inbox.zendesk-store.com).
 
### Production

1. Make sure a version bump was created for the changes.
2. Go to [Samson Zendesk Wordpress Production](https://samson.zende.sk/projects/zendesk_wordpress/stages/wordpress-org-release).
3. Check changes and deploy new tag.
4. Ask for a deploy buddy and wait until the deploy is done successfully.
5. Check if [Zendesk Wordpress Plugin Page](https://wordpress.org/plugins/zendesk/#developers) shows the correct version and the correct changelogs.
 
## Recovery

In cases of bad deploy:
1. Revert PR or create another PR that removes the defective change.
2. Wait for review and two :+1:'s before merge.
3. Create a new release tag.
4. Deploy the new tag. Deploy Process for environment should still be followed.