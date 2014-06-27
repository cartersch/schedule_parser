Schedule Parser
==============

This is a little project I created for taking a text file and turning it into a schedule for a workshop or conference.  The goal is to allow organizers to easily shift names and times around and get an idea of what a schedule may look like before committing to anything and publishing the schedule.

Requirement
-----------

- PHP 5.4 or higher
- a modern webbrowser


Instructions
------------

It's best to start with the sample template as it will already have many of the 'Fixtures' for each day of a typical workshop. The file is constructed like so:

- Each day starts with two Hashtags (##) and the word 'Day'
- Each daily schedule consists of three sections that start with one hashtag(#) and a keyword
    - Fixtures
    - Other
    - Speakers
- *Fixtures*  hold the typical items that would happen on a typical day (shuttles, breakfast, lunch, etc).
    - Each fixture item is on a separate line and consists of two to three parts separated by a pipe (|)
        - start time (HH:MMAA)
        - end time (HH:MMAA) (Optional for shuttle times)
        - Fixture name (breakfast, lunch, etc)
    - Example:
    
            8:00AM |  Shuttle Pick Up to MBI
            8:15AM | 9:00AM | Breakfast
            12:00PM | 2:00PM | Lunch
            5:15PM | Shuttle Pick Up to

- *Other* holds items specific to that day which are not fixtures or speakers (discussion, break out session, etc.)
    - Each fixture item is on a separate line and consists of three parts separated by a pipe (|)
        - start time (HH:MMAA)
        - end time (HH:MMAA) 
        - Other Item name (Discussion, Breakout Session, etc)
    - Example:
    
            9:00AM | 9:15AM | Workshop Introduction
            4:30PM | 5:00PM | Discussion
            
- *Speaker* holds the list of speakers in the order they are to talk for that day
    - Each speaker item is on a separate line and consists of at least three parts separated by a pipe (|)
        - Length of Talk
        - The name of the speaker
        - If schedule item(s) is to appear after a talk (discussion, break, etc), use the following format:
             - Length of item
             - Item name (discussion, break, etc)
        - If no scheduled item is to appear after the talk place a zero (0)
    - Example: 
    
            50 | Speaker Mon 1 | 10 | Discussion | 30 | Break
            60 | Speaker Mon 2 | 0
            60 | Speaker Mon 3 | 15 | Break