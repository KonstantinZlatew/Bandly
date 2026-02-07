import requests

url = "http://127.0.0.1:8000/evaluate-form"

# Prepare form data
data = {
    'task_type': 'academic_task_1',
    'task_prompt': 'You should spend about 20 minutes on this task. The pie chart shows the amount of money that a children s charity located in the USA spent and received in one year, 2016. Summarise the information by selecting and reporting the main features and make comparisons where relevant. Write at least 150 words.',
    'essay': 'The pie charts show the amount of revenue and expenditures in 2016 for a children’s charity in the USA. Overall, it can be seen that donated food accounted for the majority of the income, while program services accounted for the most expenditure. Total revenue sources just exceeded outgoings. In detail, donated food provided most of the revenue for the charity, at 86%. Similarly, with regard to expenditures, one category, program services, accounted for nearly all of the outgoings, at 95.8%. The other categories were much smaller. Community contributions, which were the second largest revenue source, brought in 10.4% of overall income, and this was followed by program revenue, at 2.2%. Investment income, government grants, and other income were very small sources of revenue, accounting for only 0.8% combined. There were only two other expenditure items, fundraising and management and general, accounting for 2.6% and 1.6% respectively. The total amount of income was $53,561,580, which was just enough to cover the expenditures of $53,224,896.'
}

# Open and upload the image file
with open('D:/Koko/diplomna_things/academic_task_1_01.png', 'rb') as image_file:
    files = {
        'image': image_file
    }
    
    response = requests.post(url, files=files, data=data)
    print(response.json())