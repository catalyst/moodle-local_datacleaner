# Environment Matrix Cleaner

Use
---
This subplugin will swap configurations in the admin settings, depending on the environment that the wash is being run on. Configure the environments in production, and the configs that need to be swapped, and during refresh, the data will be swapped in.

The plugin searches the admin tree for controls that match the config object that needs to be swapped out. When a matching config control is found, the plugin attempts to use the write_setting() function of the control to update the values. Some admin controls may expect data of a different type in write_setting(), such as an array. If the write_setting fails to run, due to mismatch in data types, the plugin instead falls back to using set_config(), to modify the database correctly, so the config will always be written.

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

