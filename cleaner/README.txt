
A cleaner can do almost anything. But they broadly fit into a few categories:

* A cleaner delete data

* A cleaner transforms data, by inserting random data or randomizing the existing data


As a general rule for speed, all the data removal steps should be run first
so we aren't wasting time transforming them only to be deleted later. Each
task has a sortorder which reflects this order, a good rule of thunb is to
use the CONTEXT_ numbers, so CONTEXT_SYSTEM (10) cleaners will be run first,
then CONTEXT_USER (30) etc.
