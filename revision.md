## Revisions i should do told by my classmate

# counterra
- in importing results from voterra both will have a import log table for ease make manul and 3g have seperate tabs with their onwn table or whatever is mpore good
- id, import_key, expected_votes, recieved_votes, transmission,
- import key is kinda like unique string, expected votes is the num of votes , recieved is what actually was received , transmission is basically either smooth or interupted
- then we wil have an action button to aprove or deny 

# votera
- have an export log table mirrors counterra import log table
- just extra security import key export key checks for matches , basically we make a row when exporting send those details tpo the parent so for reuploads , already recieved import_key blocks , even if this was bypassed there was still the ballot id matching so its like double security

- then mostly behaviour changes, for example voting occurs in one day and since its already mentioned what precinct         you will vote they most likely have exact balot  numbers so once voterra config is done no importing ballots , change it to show machien configured complete proceed with votings

- after configuring immediately print an election return pdf shwing locall tally but in formal document form since its nely configured voterra should show candidates with 0 votes 

- exporting is done at the end of day when voting period is finished not partial exporting so 

- keep what w ehave just hide the option for untransmitted all hardcode it to all cause we will only export one time in the machine 

- if we already export to json block 3g cannot be pressed and vice verssa 

- also once export is done voting closes so the voting view should show coting period closed await reslts in news or report

- should i add a manual  print election return pdf in local tally or keep it triggered via initial config and final export once voting period is done

- lastly after admin logs in its not goe immediatley to dashboard if the machien has not yet configured there are basically 2 speciall officers withg the admin each with their own key
- simply reuse the admin auth view after loging in is good show another version of page but isntea dof admin it simply titled and says Special passkey 1 if good proceed to special passkey 2 if good then dhasboard if machine configured admin login is all well be needed for every login then else in machien not configured every wrong in those 3 auth resets to admin auth

lets just hardcode the username and password for those 2 as well

username authofficer1 and authofficer2 with passkey just think of a 16 string for each save them in an md so i can see what they are 

just hard code them in forntend for ease in demo 

