# Environment Matrix Cleaner

Use
---
This subplugin will swap configurations in the admin settings, depending on the environment that the wash is being run on. Configure the environments in production, and the configs that need to be swapped, and during refresh, the data will be swapped in.

The plugin uses set_config to update the database with the new configuration, then it constructs an admin_settings tree, to get the updated value back out in a format that the admin_setting can use. After this, it passes that value back in, using write_setting, to perform any addition checks, validation, or custom functionality that controls, including custom controls, that may be required.

Testing
-------

| Config                                | Type                      | Action                                                   | Status  |
|---------------------------------------|---------------------------|----------------------------------------------------------|---------|
|core:profileroles                      | config_multicheckbox      | Requires array for write_setting, defaults to set_config | WORKING |
|core:lockoutwindow                     | config_duration           | Requires array for write_setting, defaults to set_config | WORKING |
|core:passwordpolicy                    | config_checkbox           | Set using write_setting                                  | WORKING |
|core:maxbytes                          | config_select             | Requires array for write_setting, defaults to set_config | WORKING |
|core:userquota                         | config_text               | Set using write_setting                                  | WORKING |         
|tool_securityquestions:questionfile    | config_text               | Set using write_setting                                  | WORKING |
|tool_securityquestions:questionduration| config_duration           | Requires array for write_setting, defaults to set_config | WORKING |
|auth_saml2:idpmetadata                 | custom_config_idpmetadata | Set using write_setting, correctly updates IDP list      | WORKING |
|auth_saml2:idpname                     | config_text               | Set using write_setting                                  | WORKING |
|auth_saml2:showidplink                 | config_select             | Requires array for write_setting, defaults to set_config | WORKING |

