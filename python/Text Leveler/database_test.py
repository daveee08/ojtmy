# 127.0.0.1
# root
# ck_agent

import mysql.connector

# Connect to the database
mydb = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password="",
    database="ck_agent",
)

session = ""  # edit add first without inserting any in id in session and add in messages_id in messages table

# add in parameter_inputs table the parameter_id, input, agent_id and loop for each agent_id to get the parameter_id to insert in inputs table
agent_id = "4" 
parameter_id = "4" # edit this to search on parameter_inputs table the agent_id and get the parameter_input id for each same agent_id
input = "Grade 5" # edit this to input on how many parameters the agent has example if the agent has 2 parameters then add in a row the parameter_id from the agent_parameters table on what agent and its agent_id and add the additional input for different parameter_id for a different parameter of the agent but the same agent_id

# add  in parameter_references table the parameter_inputs_ids and parameter_id
parameter_ids = "1,2" #  edit This should be a comma-separated string of parameter input IDs, this will put the ids of the parameters_input of the same agent_id that are used in the message agent
parameter_inputs_id = "4"  # edit This is the parameter_id that will be used to reference the parameter_inputs_ids

# add in messages table the agent_id, user_id, message_id, parameter_inputs, sender, topic
user_id = "1"
message_id = session
sender = "human"
topic = "Sample Topic"

mycursor = mydb.cursor()

# Insert into parameter_inputs table
sql_param = """
INSERT INTO parameter_inputs (input, parameter_id, agent_id) VALUES (%s, %s, %s)
"""
val_param = (input, parameter_id, agent_id)
mycursor.execute(sql_param, val_param)

parameter_input_id = mycursor.lastrowid

# Insert into messages table
sql_msg = """
INSERT INTO messages (agent_id, user_id, message_id, parameter_inputs, sender, topic)
VALUES (%s, %s, %s, %s, %s, %s)
"""
val_msg = (agent_id, user_id, message_id, parameter_input_id, sender, topic)
mycursor.execute(sql_msg, val_msg)

# Commit the transaction
mydb.commit()

print(mycursor.rowcount, "record(s) inserted.")